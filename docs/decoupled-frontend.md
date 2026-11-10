# Connecting a Next.js Front End on Vercel to a Drupal Back End

This guide covers how each Lagunita site's Drupal back end (on Acquia) and Next.js front end (on Vercel) are
connected, and how to set up or repair that connection. Continuing Studies (CSP) is the worked example; swap in your
own site's names. Related ticket: CSP-40.

The front-end behavior described here was checked against the `cardinalsites-nextjs` template and the `1.x` branch
of each site's front-end repo on 2026-11-10.

## How the pieces fit together

```
                 ┌──────────────────────────── Acquia Cloud ────────────────────────────┐
 Editors ──────▶ │  Drupal 11 multisite (ace-stanfordlagunita)                          │
                 │   /graphql (POST, Basic Auth)   ◀── page data, menus, views          │
                 │   Next module ── on save ──────▶ {front end}/api/revalidate?secret=… │
                 │   Next module ── preview iframe ▶ {front end}/preview?secret=…&slug=…│
                 └──────────────────────────────────────────────────────────────────────┘
                                 ▲                              │
                         GraphQL │                              │ revalidate / preview
                                 │                              ▼
                 ┌──────────────────────────────── Vercel ──────────────────────────────┐
 Visitors ─────▶ │  Next.js app (e.g. csp-nextjs)                                       │
                 │   Pages cached indefinitely, purged by tag when Drupal saves content │
                 │   Secrets can be loaded at build time from Stanford Vault (AppRole)  │
                 └──────────────────────────────────────────────────────────────────────┘
```

The connection runs both ways:

- **Front end → Drupal:** Next.js reads content over GraphQL, logging in with HTTP Basic Auth as a dedicated API
  user.
- **Drupal → front end:** the Drupal [Next](https://www.drupal.org/project/next) module calls the front end's
  revalidate endpoint when content changes, and loads the front end's preview route in an iframe so editors can see
  drafts.

Each side holds shared secrets that the other side must match. **Most setup problems come from these values not
matching.**

### Current sites

From `docroot/sites/<site>/settings/includes.settings.php` and `project.frontend-repos` in `drush/drush.yml`:

| Site      | Front-end repo      | Vercel team               | Vercel project              | Next site machine name | Preview path set in Drupal | Uses Vault |
|-----------|---------------------|---------------------------|-----------------------------|------------------------|----------------------------|------------|
| `csp`     | `csp-nextjs`        | `sws-developers`          | `continuingstudies`         | `prod`                 | `/preview`                 | Yes        |
| `library` | `sulgryphon-nextjs` | `stanford-libraries`      | `su-library`                | `netlify`              | `/api/draft`               | No         |
| `summer`  | `summer-nextjs`     | `stanford-summer-session` | `summer`                    | `vercel`               | `/api/draft`               | Yes        |
| `supress` | `press-nextjs`      | `stanford-press`          | `stanford-university-press` | `vercel`               | `/api/draft`               | No         |

All four front-end repos use `1.x` as the production branch and have long-lived `dev` and `test` branches.

**Preview path:** all four front ends now handle previews at `/preview`. `/api/draft` is a legacy entry point that
Library, Summer and Press still accept and redirect to `/preview` (Library and Summer through a redirect in
`next.config.ts`, Press through an `app/api/draft` route). Drupal can be switched to `/preview` for those sites; until
then, keep `/api/draft` working on their front ends.

**Vault:** "Uses Vault" means the repo has a `vault-envars.ts` loader. Sites without one set every variable directly
in Vercel.

## Credentials and where they live

Credentials are easiest to handle if they are alphanumeric only. Special characters work, but may need escaping or
quoting in `.env.local` files. To generate one:

```bash
cat /dev/urandom | LC_ALL=C tr -dc 'a-zA-Z0-9' | head -c 32; echo
```

| Credential                                      | Purpose                                         | Drupal side                          | Vercel side                                        |
|-------------------------------------------------|-------------------------------------------------|--------------------------------------|----------------------------------------------------|
| Read-only API user (`nextjs`)                   | Published GraphQL reads                         | User account in the site database    | `DRUPAL_BASIC_AUTH` = `user:password`              |
| Preview API user (`nextjsAdmin`)                | Reads unpublished content for preview           | User account in the site database    | `DRUPAL_BASIC_AUTH_ADMIN` = `user:password`        |
| Revalidate secret                               | Authorizes Drupal's cache-purge calls           | Next site entity → *Revalidate secret* | `DRUPAL_REVALIDATE_SECRET`                       |
| Preview secret                                  | Authorizes the editor preview route             | Next site entity → *Preview secret*  | `DRUPAL_PREVIEW_SECRET`                            |
| WAF bypass header (optional)                    | Lets build traffic past edge rate limits        | WAF / CDN rule                       | `DRUPAL_REQUEST_HEADERS` (JSON)                    |
| Vault AppRole (optional)                        | Lets the build fetch the other values from Vault | n/a                                  | `VAULT_ROLE_ID`, `VAULT_SECRET_ID`, `VAULT_PATH`   |
| `/system/cache-clear` login                     | Manual cache-clear page on the front end        | n/a                                  | `CACHE_CLEAR_USERNAME`, `CACHE_CLEAR_PASSWORD`     |
| SAML / JWT (only for sites with protected pages) | SSO for `/internal/*` and `/user`              | n/a                                  | `SAML_*`, `JWT_SECRET`                             |

**Source of truth:** sensitive values are stored in Stanford Vault at
`secret/data/projects/uit-webservices/decoupled-drupal/<site>`. Front ends that use Vault have a `vault-envars.ts`
loader, which `next.config.ts` calls and spreads into the Next.js `env` config. It logs in with the AppRole, reads every
key at that path, and loads each one as an environment variable. Things to know:

- **A variable set directly in Vercel overrides the Vault value.** The loader skips any key that is already set.
- **Vault is read when the app is built**, because Next.js inlines `env` config values at build time. After you
  change a value in Vault, redeploy the front end.
- If the AppRole login or the read fails, the loader logs `[Vault] ...` and the build carries on without those
  values. Check the build log for `[Vault] Secrets loaded successfully`.

> **Warning:** changing an API user's password breaks the front end immediately. Don't reset it (with
> `drush user:password`, the UI, or `drush su-next-connect`, see below) unless you also update Vault or Vercel,
> redeploy, and tell every developer to update their `.env.local`.

Back-end hostnames, and which are behind Akamai, are listed in the [README](../README.md#sites). If the front end
can't reach Drupal, the request may be blocked at the Akamai edge and never appear in Drupal or Acquia logs.

## Drupal / Acquia setup

### Required modules

These are already enabled in the Lagunita profiles. Check them on a new profile:

- `next`: revalidation and preview
- `graphql`, `graphql_compose` and its submodules (`_routes`, `_menus`, `_views`, `_metatags`, `_layout_paragraphs`,
  `_edges`, `_image_style`, …)
- `basic_auth`: how the front end authenticates
- `config_ignore`: keeps per-environment Next settings out of config sync

### Next site config is not in config sync

Front-end URLs and secrets differ per environment, so each profile's `config/sync/config_ignore.settings.yml`
ignores:

```yaml
ignored_config_entities:
  - 'next.next_entity_type_config.*'
  - 'next.next_site.*'
```

These values therefore live **in each environment's database**, not in git:

- `drush cim` and deployments won't overwrite them.
- Copying a database between Acquia environments, or syncing one locally, **does** copy them along. The URLs are then
  corrected by the per-environment overrides below, but the **secrets are the prod secrets**, so dev, test and local
  front ends must use the same preview and revalidate secrets as prod unless you change them after the copy.

### Configure Drupal with Drush

The quickest approach is:

```bash
drush @<site>.<env> su-next-connect https://<front-end-url> --id=<machine name>
```

(`su-next-connect` is the alias of `stanford-decoupled:connect-next`, from the `stanford_decoupled` submodule of
`stanford_profile_helper`.) It:

- Creates or updates the Next site entity with ID `--id` (default `local`), sets the base URL, sets the revalidate
  URL to `/api/revalidate` and the preview URL to `/preview`, and generates new preview and revalidate secrets unless
  you pass `--preview-secret` and `--revalidation-secret`.
- Creates the `nextjs` and `nextjsAdmin` users if needed, adds the `decoupled_site_users` role (and `administrator`
  for `nextjsAdmin`), and **sets a new random password on both users every time it runs**.
- Unless you pass `--invisible`, sets the default theme to `stanford_profile_admin_theme` and turns on the
  `stanford_intranet` state. With `--invisible`, it turns off *Draft preview* on all Next entity type configs instead.
- Prints the `DRUPAL_PREVIEW_SECRET`, `DRUPAL_REVALIDATE_SECRET`, `DRUPAL_BASIC_AUTH` and `DRUPAL_BASIC_AUTH_ADMIN`
  values to put in Vault or Vercel (`--format=json` for JSON).

Things to watch for:

- Pass `--id` matching the machine name in the site's `includes.settings.php` (see the table above). Otherwise a
  second Next site called `local` is created and the per-environment URL overrides don't apply to it.
- The command always sets the preview URL to `/preview`. That works on all four current front ends, but it differs
  from the `/api/draft` path that `includes.settings.php` sets for Library, Summer and Press on dev, test and local.
- Because it resets both API passwords, running it on an existing environment breaks that environment's front end
  until the new values are deployed. On an existing site, prefer the manual steps below.

### Configure Drupal manually

#### Create the API users

1. Create an authenticated user (`nextjs`) to read published content through GraphQL. Give it the **Decoupled Site
   User** role.
2. Create a preview user (`nextjsAdmin`) with a role that can also view unpublished content and revisions, preferably
   **Administrator**. Also give it the **Decoupled Site User** role.
3. Give each a long random password and store `user:password` in Vault as `DRUPAL_BASIC_AUTH` /
   `DRUPAL_BASIC_AUTH_ADMIN`.

The Decoupled Site User role (`decoupled_site_users`) is what keeps these credentials working after a database sync:
`drush/drush.yml` sets `command.sql.sanitize.options.ignored-roles: decoupled_site_users`, so `sql:sanitize` doesn't
reset these users' passwords.

Check that the credentials work (with `DRUPAL_BASIC_AUTH` set to `nextjs:<password>` in your shell):

```bash
curl -s -u "$DRUPAL_BASIC_AUTH" -H 'Content-Type: application/json' \
  -X POST https://<drupal-host>/graphql \
  --data '{"query":"{ route(path: \"/\") { __typename } }"}'
```

You should get JSON back, not a 403 or a login page.

#### Point Drupal at the front end (Next site entity)

Go to **Configuration → Web services → Next.js sites** (`/admin/config/services/next`) and add or edit the site:

| Field                | Value                                                                                         |
|----------------------|-----------------------------------------------------------------------------------------------|
| Label / machine name | Must match the key used in the site's `includes.settings.php` (see the table above).          |
| Base URL             | The front-end URL for this environment                                                        |
| Preview URL          | `{base}/preview`. Library, Summer and Press currently use the legacy `{base}/api/draft`, which redirects to `/preview`. |
| Preview secret       | Random string, the same as `DRUPAL_PREVIEW_SECRET`                                            |
| Revalidate URL       | `{base}/api/revalidate`                                                                       |
| Revalidate secret    | Random string, the same as `DRUPAL_REVALIDATE_SECRET`                                         |

Then go to **Next.js entity types** (`/admin/config/services/next/entity-types`) and turn on revalidation for each
content entity type and bundle the front end renders (nodes, plus any menus or config pages that should be purged on
save).

### Per-environment URL overrides

Each site's `docroot/sites/<site>/settings/includes.settings.php` replaces the Next site **URLs** (not the secrets)
on non-prod environments, so a database copied from prod doesn't send revalidation calls to production:

```php
$next_domain = FALSE;
if (EnvironmentDetector::isDevEnv()) {
  $next_domain = 'https://continuingstudies-git-dev-sws-developers.vercel.app';
}
elseif (EnvironmentDetector::isStageEnv()) {
  $next_domain = 'https://continuingstudies-git-test-sws-developers.vercel.app';
}
elseif (EnvironmentDetector::isLocalEnv()) {
  $next_domain = 'http://localhost:3000';
}

if ($next_domain) {
  $config['next.next_site.prod'] = [            // Machine name of the Next site.
    'base_url'       => $next_domain,
    'preview_url'    => "$next_domain/preview",  // Legacy /api/draft on Library, Summer, Press.
    'revalidate_url' => "$next_domain/api/revalidate",
  ];
}
```

Prod has no override and uses the values stored in the prod database.

The dev and test URLs are Vercel **branch URLs** (`<project>-git-<branch>-<team-slug>.vercel.app`). So the front-end
repo needs `dev` and `test` branches, and the Vercel project and team names are part of the URL. **If you rename
either, update this file.**

### Settings already handled for headless sites

`docroot/sites/settings/config.settings.php` applies to every site: it disables `domain_301_redirect`, sets
`nobots`, clears the Google Analytics account, makes the Algolia index read-only outside prod, and disables Shield.

### Purging a single page

To make Drupal send a revalidation request for one path, run:

```bash
drush @<site>.<env> su-next-invalidate /some/path
```

## What the front end exposes

These are the endpoints in the `cardinalsites-nextjs` template, which the site front ends follow:

| Route                    | Used by            | Auth                                                                                  | Behavior                                                                                       |
|--------------------------|--------------------|---------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------|
| `GET /api/revalidate`    | Drupal Next module | `?secret=` must equal `DRUPAL_REVALIDATE_SECRET`                                      | Purges the cache tag `paths:<path>` for `?path=`. A `/node/<nid>` path is resolved to its alias first, and `/tags/a/b` purges tags `a` and `b`. Saving the home page also purges `/`. |
| `POST /api/revalidate`   | Scripts            | `Authorization: Bearer <DRUPAL_REVALIDATE_SECRET>`                                    | Purges a JSON body of `{"paths": [...], "tags": [...]}`.                                       |
| `/preview?secret=&slug=` | Drupal preview iframe | `secret` must equal `DRUPAL_PREVIEW_SECRET`, checked in `proxy.tsx`                | Redirects to `/preview<slug>?secret=…` and renders the draft, uncached, with `DRUPAL_BASIC_AUTH_ADMIN`. |
| `/system/cache-clear`    | Developers         | HTTP Basic Auth with `CACHE_CLEAR_USERNAME` / `CACHE_CLEAR_PASSWORD`                  | Form to purge paths or tags by hand.                                                           |
| `/internal/*`, `/user`   | Visitors           | SAML login, then a JWT cookie                                                         | Protected pages. Remove these from the `proxy.tsx` matcher if the site has no protected pages. |

Preview fails closed: if `DRUPAL_PREVIEW_SECRET` isn't set, or the secret or slug is wrong, the front end returns its
404 page, not a 403. Preview responses are sent with `noindex`, `no-store` and `no-referrer` headers, because the URL
carries the secret.

For GraphQL requests, the front end sends `DRUPAL_BASIC_AUTH`, or `DRUPAL_BASIC_AUTH_ADMIN` in preview (falling back
to `DRUPAL_BASIC_AUTH` if the admin value isn't set). Headers from `DRUPAL_REQUEST_HEADERS` are added to every
request. If that value isn't valid JSON, it's skipped with a warning.

## GitHub setup (new front end)

1. Create the front-end repo under `SU-SWS`, for example by copying or forking (`gh repo fork`) `cardinalsites-nextjs`.
   The production branch is `1.x`.
2. Add the repo name to `project.frontend-repos` in this repo's `drush/drush.yml`, so CI builds it against the
   site's database.
3. Create long-lived `dev` and `test` branches. Vercel builds each at a stable branch URL, and those are the URLs the
   Acquia dev and test environments revalidate against (see [Per-environment URL overrides](#per-environment-url-overrides)).
   To create one, check out a new branch from `1.x`, make a small change (such as the `description` in
   `package.json`), and push it.
   The template's `.github/workflows/update-dev-test-branches.yml` rebases `dev` and `test` onto `1.x` and
   force-pushes them on every push to `1.x`, so both branches must exist, and work committed only to `dev` or `test`
   is replayed on top of `1.x` each time.
4. Check that `.github/workflows/build_lint.yml` is present. On every push it runs `yarn lint` (ESLint and `tsc`) in
   one job. In another, it checks out this repo (`SU-SWS/ace-stanfordlagunita`), sets up Drupal, appends the output of
   `drush stanford-decoupled:connect-next` to `.env.local`, runs `yarn build` with `BUILD_PAGES` set (100 or 500),
   and then runs HTML and accessibility validation. That catches GraphQL schema drift before deploy.
   - The template's "Sync Site" step installs a base site (`drush drupal:install`). Change it to sync the real site,
     as the site repos do: `drush drupal:sync --site=<site> -n`.
   - Syncing needs the repository secrets `SSH_KEY` (a key with Acquia access) and `KNOWN_HOSTS`.
5. The Vercel GitHub app is installed on the repo when you import the project in Vercel (below).

## Vercel setup

### Create the project

1. In the right Vercel team (see the table above), go to **Add New → Project** and import the GitHub repo.
2. **Framework preset:** Next.js. **Root directory:** repo root.
3. Build and install commands come from `vercel.json` in the repo. Don't override them in the UI:

   ```json
   {
     "buildCommand": "yarn build",
     "installCommand": "yarn workspaces focus --production"
   }
   ```

4. **Node.js version:** match `.nvmrc` (Settings → Build & Deployment).
5. **Production branch:** `1.x` (Settings → Git).
6. Choose the project name carefully. It is the prefix of every branch URL (`continuingstudies-git-dev-…`) that
   Drupal is configured with. It can be changed later, but then `includes.settings.php` must be updated too.

### Environment variables

Set these under Settings → Environment Variables. Any of them can be set in Vercel directly or stored in Vault and
loaded with the AppRole credentials.

| Variable                            | Production                                               | Preview (`dev`, `test`, PRs)                                                          | Notes                                                                          |
|-------------------------------------|----------------------------------------------------------|---------------------------------------------------------------------------------------|--------------------------------------------------------------------------------|
| `NEXT_PUBLIC_DRUPAL_BASE_URL`       | Prod Drupal URL (e.g. `https://edit-csp.stanford.edu`)   | The matching Acquia dev/test URL. Use **branch-specific** values: `dev` → Acquia dev, `test` → Acquia test | Required. Inlined at build time, so redeploy after changing it.                |
| `VAULT_ROLE_ID` / `VAULT_SECRET_ID` | Yes                                                      | Yes                                                                                   | AppRole for this site                                                          |
| `VAULT_PATH`                        | `secret/data/projects/uit-webservices/decoupled-drupal/<site>` | Same, or a non-prod path if secrets differ per environment                      |                                                                                |
| `NEXT_PUBLIC_DOMAIN`                | Canonical public domain                                  | **Leave unset**                                                                       | While unset, every response gets `X-Robots-Tag: noindex,nofollow,noarchive`. It also sets the domain used in `sitemap.xml`. Only set on Production, and only at launch. |
| `BUILD_PAGES`                       | `0` to a few hundred                                     | `0`                                                                                   | Pages pre-rendered at build. `0` builds only the home page; `-1` builds all pages (slow, and hard on Drupal). Keep it under about 500. |
| `NEXT_PUBLIC_GTM`                   | GTM container ID                                         | unset                                                                                 | Optional                                                                       |

If a secret differs between environments and Vault only holds one value, set the differing value directly in Vercel
for that environment. Vercel variables override Vault.

## Troubleshooting checklist

- **GraphQL returns 403 or a login page:** the `DRUPAL_BASIC_AUTH` user or password is wrong, or the user is missing
  the needed role. Test with the `curl` command above. If the request never shows up in Drupal logs, check the Akamai
  security logs.
- **Saving content doesn't update the front end:** check the Next site's revalidate URL and secret match the front
  end's `DRUPAL_REVALIDATE_SECRET`, and that revalidation is on for that entity type and bundle.
- **Preview shows a 404:** the preview secret doesn't match `DRUPAL_PREVIEW_SECRET`, `DRUPAL_PREVIEW_SECRET` isn't
  set on the front end, or the slug was rejected. A 404 for an unpublished page can also mean
  `DRUPAL_BASIC_AUTH_ADMIN` is missing, so the request fell back to the read-only user.
- **Revalidate returns `403 Invalid token`:** the Next site's revalidate secret doesn't match
  `DRUPAL_REVALIDATE_SECRET`.
- **A Vault change has no effect:** Vault is read at build time, so redeploy. Also check that the same variable isn't
  set directly in Vercel, since that takes precedence.
- **Dev or test revalidates prod (or nothing):** the Next site machine name doesn't match the key in
  `includes.settings.php`, or the Vercel project or team was renamed.
- **Everything broke after a database copy or `su-next-connect`:** the secrets or API passwords changed. Update Vault
  or Vercel and redeploy.
