# SUPress Helper

Site-specific functionality for the Stanford University Press (SUPress) site.
Most of it exists to import book data from the press's FileMaker database.

This document covers how the module talks to the FileMaker Data API. For the
API itself, see the
[Claris FileMaker Data API guide](https://help.claris.com/en/data-api-guide/).

## FileMaker Data API

All data comes from the `Web` database on `https://memento.stanford.edu`.
Every url below is relative to:

```
https://memento.stanford.edu/fmi/data/v2/databases/Web
```

### Credentials

The API user and password are saved in the site settings config page
(`stanford_basic_site_settings`), in the `sup_filemaker_user` and
`sup_filemaker_pass` fields. They are never stored in code or exported config.

They reach the code in two ways:

- `PressHelper::getApiToken()` and `BookCoverDownloader` read the config page
  fields directly.
- Migrations use the `sup_press` migration group's
  `shared_configuration.source.authentication` settings. The exported config
  contains `[client_id]` and `[client_secret]` placeholders, which
  `config_pages_overrides` replaces with the field values at runtime (see
  `config_split.patch.config_pages.type.stanford_basic_site_settings.yml`).
  The same overrides set each migration's `status`, so the migrations are only
  enabled once credentials have been saved.

### Authentication

`POST /sessions` with HTTP basic auth returns a session token:

```json
{"response": {"token": "..."}, "messages": [{"code": "0", "message": "OK"}]}
```

Every other request sends the token as `Authorization: Bearer <token>`.
FileMaker expires a session after about 15 minutes without use, and the
number of open sessions is limited by the server license, so reuse tokens
rather than logging in for each request.

| Consumer | Session reuse |
|---|---|
| `PressHelper::getApiToken()` | Cached in `cache.default` (`filemaker_api_token`) for 5 minutes. Returns `FALSE` without credentials, during site install, or if the login fails. |
| `press_filemaker` authentication plugin (`FileMakerAuth`) | One session per plugin instance, so one per migration run. Fails with a `MigrateException` if settings are empty or still placeholders, or if no token is returned. |
| `BookCoverDownloader` queue worker | Logs in for every queue item. |

### Requests

There are two request styles:

- **List records:** `GET /layouts/{layout}/records?_limit=N&_offset=N`.
  `_offset` is 1-based. `response.dataInfo.totalRecordCount` gives the total
  number of records in the layout.
- **Find records:** `POST /layouts/{layout}/_find` with a JSON body:

  ```json
  {
    "query": [{"format_pdf": "=yes"}, {"format_epub": "=yes"}],
    "sort": [{"fieldName": "retailer", "sortOrder": "ascend"}],
    "limit": 1000,
    "offset": 1
  }
  ```

  Each object in `query` is OR'd with the others, and fields within one object
  are AND'd. `=value` is an exact match, while a bare value like `http` matches
  words starting with it.

Both return records in `response.data`, each with a `recordId` and the layout's
fields in `fieldData`.

**No matching records is an error.** A find with no results responds with HTTP
500 and FileMaker message code `401` ("No records match the request") rather
than an empty list. `PressHelper::findRecords()` treats that as an empty result
and re-throws any other error.

### Layouts

| Layout | Request | Used by | Fields used |
|---|---|---|---|
| `Web` | `GET records` | `sup_import_books`, `sup_book_prices`, `sup_book_toc` migrations; `drush supress:unpublish-orphans` | One record per book, keyed by `work_id_number`. See each migration's `source.fields`. |
| `Awards` | `GET records` | `sup_awards` migration | Keyed by `recordId`; awards link to books by `work_id_number`. |
| `SocialMedia` | `GET records` | `sup_social_links` migration | `work_id_number`, `title`, `url`, `description`, `source` |
| `EbookRetailers` | `POST _find` | `PressHelper::getEbookData()`, during the `sup_import_books` migration | `work_id_number`, `retailer`, `link`, `format_pdf`, `format_epub` |
| `Covers` | `POST _find`, `GET records/{recordId}` | Cover image queue (see below) | `work_id_number`, `image` (container url), `flag` |
| `Covers` | `GET script/ClearFlags?script.param={work_id_number}` | `BookCoverDownloader` | Runs the `ClearFlags` script to clear a cover's update flag. |

## Migrations

The migrations live in the `supress` config split
(`config/sync/split/supress`) in the `sup_press` group, which sets the
FileMaker authentication, the JSON parser (`item_selector: response/data`) and
`track_changes: true` for all of them.

| Migration | Source plugin | Destination | Orphans |
|---|---|---|---|
| `sup_awards` | `supress_filemaker` | `press` entity, `award` bundle | delete |
| `sup_book_prices` | `supress_filemaker` | `press` entity, `prices` bundle | delete |
| `sup_book_toc` | `supress_filemaker` | `sup_book_ancillary` node | delete |
| `sup_import_books` | `supress_books` | `sup_book` node | none |
| `sup_social_links` | `supress_social_media` | `sup_book_ancillary` node | unpublish |

`sup_import_books` requires `sup_book_prices` and looks up awards and prices
by work id, so run the group in dependency order:

```
drush migrate:import --group=sup_press
```

### Source plugins

**`supress_filemaker`** (`SupressFileMaker`) extends the `url` source plugin
(through `stanford_migrate`'s `StanfordUrl`). Configure a single records url
with no query string:

```yaml
source:
  plugin: supress_filemaker
  urls:
    - 'https://memento.stanford.edu/fmi/data/v2/databases/Web/layouts/Web/records'
```

When the migration first needs data, the plugin requests the layout's record
count and replaces the url with one url per page of 5,000 records. Loading the
migration, for example in the migrate UI, makes no API requests.

**`supress_books`** (`SupressBooks`) extends `supress_filemaker`. On the first
row it loads all ebook data with `PressHelper::getEbookData()`, then adds these
source properties to each book by `work_id_number`:

- `ebook_retailers`: list of `title`/`uri` links, sorted by retailer.
- `epub`, `pdf`: `TRUE` if any of the book's records has the format, `FALSE`
  if it has a format record without it, `NULL` otherwise.

**`supress_social_media`** (`SupressSocialMedia`) fetches every page of the
`SocialMedia` layout itself and groups the records by `work_id_number` into one
HTML item per book. Set `url` to override the default layout url.

The paging logic is shared through `FileMakerPagedUrlsTrait`.

### Failures stop the import

A migration with no source rows treats every imported item as an orphan, and
`stanford_migrate` would then delete or unpublish all of them. To prevent that,
the source plugins throw rather than returning no data when:

- there is no API token,
- the record count, ebook data or a page request fails,
- a `supress_filemaker` layout reports 0 records.

The failed migration leaves existing content untouched.

## Cover images

FileMaker flags covers that changed with `flag = x`. On cron:

1. `PressHelper::queueCoverImages()` runs only on production, and only when the
   `press_cover_downloader` queue is empty. It finds up to 100 flagged
   `Covers` records and queues each `recordId`.
2. The `press_cover_downloader` queue worker (`BookCoverDownloader`) fetches
   the record, downloads the `image` url to `public://media/covers`, resizes it
   with the `breakpoint_2xl_1x` image style, and creates or updates the image
   media item with the matching `sup_book_work_id`. Books using the media are
   re-saved so search is re-indexed. A new media item flags the book's row in
   `sup_import_books` for update, so the next import attaches it.
3. The worker runs the `ClearFlags` script so the cover isn't queued again.
   If it fails, the cover stays flagged and is queued on a later cron run.

## Testing

```
vendor/bin/phpunit -c docroot/core docroot/profiles/lagunita/supress/modules/supress_helper/tests
```

Unit tests mock the HTTP client, so they never call FileMaker.
