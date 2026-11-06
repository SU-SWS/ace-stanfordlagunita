# AGENTS.md

Guidance for AI coding agents working in this repository (`su-sws/ace-stanfordlagunita`).
Human-oriented docs live in `README.md` and `docs/` — read `docs/architecture.md` and
`docs/testing.md` for more depth.

## Hard rules

- **Never read, edit, or reference anything in `deploy/`.** It is the build artifact directory
  (`artifact-dir: deploy` in `drush/drush.yml`), produced by `drush sws:artifact:deploy` and
  pushed to Acquia. It is gitignored and is stale/derived — it is not a source of truth, even when
  it contains a different version of a file.
- Don't commit secrets. `keys/`, `files-private/`, `local.*` settings files, and `.env` are
  gitignored; gitleaks runs in CI (`.github/workflows/secret-scan.yml`).
- The main branch is `4.x`. Pushes to `4.x` deploy an artifact automatically; merged PRs create a
  `4.x.y` release tag that is deployed. Don't push to `4.x` directly.

## What this is

One Drupal 11 (core `~11.4`, PHP ≥ 8.3, CI on PHP 8.4, Drush 13) multisite codebase hosting
**fully decoupled** back ends. Each site has its own install profile; front ends are separate
Next.js repos that read from Drupal (GraphQL / JSON:API, via the `next` module). Data flows one way:
Drupal → Next.js.

| Site     | `docroot/sites/` dir | Profile          | Acquia app         | Front-end repo       |
|----------|----------------------|------------------|--------------------|----------------------|
| CSP      | `csp`                | `csp_profile`    | `stanfordgryphon`  | `csp-nextjs`         |
| Library  | `library`            | `sul_profile`    | `stanfordlagunita` | `sulgryphon-nextjs`  |
| Summer   | `summer`             | `summer_profile` | `stanfordsummer`   | `summer-nextjs`      |
| Press    | `supress`            | `supress`        | `stanfordpress`    | `press-nextjs`       |

`default` also exists and uses `stanford_profile`. The multisite list is
`command.sws.options.multisites` in `drush/drush.yml`. See `README.md` for hostnames and the
Akamai/Acquia edge notes.

## Repository layout

What is **tracked in this repo**:

- `docroot/profiles/lagunita/<profile>/` — the four site profiles (the bulk of the tracked code).
  Each is a **git subtree** of [`SU-SWS/stanford_profile`](https://github.com/SU-SWS/stanford_profile)
  (branch `13.x`) plus site-specific customizations. Inside each:
  - `<profile>.info.yml`, `.profile`, `.install`, `.post_update.php`, `.services.yml`
  - `src/` — `Hook/` (OOP hooks), `Plugin/InstallTask/`, `Config/ConfigOverrides.php`,
    `EventSubscriber/`
  - `modules/<site>_helper/` — site-specific helper module (`sul_helper`, `summer_helper`,
    `supress_helper`; CSP uses `csp_helper/` at the profile root)
  - `themes/` — `stanford_basic`, `jemison`, `minimally_branded_subtheme`,
    `stanford_profile_admin_theme`
  - `config/sync/` — the site's config sync directory; `config/install/`
  - `content/` — default content (UUIDs listed under `default_content` in the info file)
  - `tests/src/{Unit,Kernel}` (PHPUnit), `tests/codeception/{acceptance,functional}`
  - `composer.json` — merged into the root via `wikimedia/composer-merge-plugin`; declares PSR-4
    namespaces for the profile and helper module (add new test/helper namespaces here)
- `docroot/sites/<site>/` — `settings.php`, `settings/includes.settings.php` (sets
  `config_sync_directory` and the Next.js preview/revalidate URLs per environment), `sws.yml`
  (profile + remote alias), `blt.yml`. Shared settings in `docroot/sites/settings/`.
  `docroot/sites/sites.php` maps `*.sites-pro.stanford.edu` / `*.stanford.edu` hostnames to dirs;
  local hostnames go in the gitignored `local.sites.php`.
- `config/envs/{local,dev,stage,prod}/` — environment config splits shared by all sites
  (purge, seckit, shield, paranoia, stage_file_proxy, devel…). `config/default/`.
- `drush/` — `drush.yml` (multisites, Acquia git remotes, front-end repo map),
  `drush/lagunita/src` (namespace `Drupal\LagunitaDrush`, provides `drush lagunita:new-profile`),
  `drush/sites/` aliases, `deploy-cleanup.sh` (artifact post-build).
- `hooks/` — Acquia Cloud hooks (`common/post-code-deploy` runs
  `drush sws:multisite:update:parallel`; `post-db-copy` scrubs DBs).
- `patches/` — composer patches (listed in root `composer.json` → `extra.patches`) plus
  `htaccess.patch` / `robotstxt.patch` applied after scaffolding.
- `tests/` — Codeception config templates (`codeception.dist.yml`, `*.suite.dist.yml`) and
  `_support`. Per-site tests live in each profile.
- `.github/workflows/` — `tests.yml`, `deploy-main-branches.yml`, `release.yml`,
  `secret-scan.yml`, `sup-check-pages.yml`, `weekly-cleanup.yml`.
- `.ddev/`, `lando/`, `.gitpod.yml` — optional local environments.

What is **installed by Composer and gitignored** (do not expect changes here to show up in this
repo's `git status`):

- `vendor/`, `docroot/core`, `docroot/modules/contrib`, `docroot/libraries`, `drush/Commands`.
- `docroot/modules/custom/*` and `docroot/profiles/custom/stanford_profile` — Stanford packages
  (`su-sws/stanford_profile_helper`, `stanford_migrate`, `stanford_media`, `stanford_fields`,
  `stanford_samlauth`, `react_paragraphs`, `stanford_actions`, `ckeditor5_plugins`,
  `stanford_profile`). These are installed from source, so **each is its own git checkout** of its
  upstream repo. Edits there must be committed/branched inside that directory (e.g.
  `git -C docroot/modules/custom/stanford_profile_helper status`), and they reach this project
  only by updating `composer.lock`.

## Configuration management

- Each site's `config_sync_directory` is its profile's `config/sync`
  (`docroot/profiles/lagunita/<profile>/config/sync`). `default` uses
  `profiles/custom/stanford_profile/config/sync`.
- Each profile has an always-on site config split (`config_split.config_split.<site>.yml`, folder
  `profiles/lagunita/<profile>/config/<site>`) to isolate differences from `stanford_profile`, plus
  environment splits (`local/dev/stage/prod`) pointing at `../config/envs/<env>`, toggled by
  environment in settings.
- When exporting config, review where files landed — prefer putting site-specific changes in the
  split (adjust Complete/Partial split settings) rather than diverging `config/sync`. Entity
  displays don't work well as split patches.
- Many config files are duplicated across the four profiles (and across the
  `migrate_plus.migration.*` files). When fixing something shared, check whether it needs to be
  applied to each profile — or upstream in `stanford_profile`.
- Some config is overridden at runtime by `config_pages` / `ConfigOverrides` classes, and
  `config_readonly` is enabled on hosted environments.

## Pulling upstream `stanford_profile` changes

```
composer pull-sul | pull-press | pull-summer | pull-csp
```

Each runs `git subtree pull --squash` from `stanford_profile` `13.x` into one profile and keeps
"ours" for the profile's `composer.json` and `.info.yml`. Expect merge conflicts that need manual
resolution. A new site is scaffolded with
`drush lagunita:new-profile <profile_name> <site_name> <config_prefix>`.

## Code conventions

- Drupal coding standards (`phpcs.xml.dist`: `Drupal` + `DrupalPractice`). Run e.g.
  `vendor/bin/phpcs --standard=phpcs.xml.dist <path>`.
- Prefer modern Drupal 11 patterns, which the codebase is actively migrating to:
  - **OOP hooks**: classes in `src/Hook/` with `#[Hook('hook_name')]` methods and constructor
    injection (see `sul_helper/src/Hook/SulHooks.php`). Older procedural hooks still exist in
    `.module` files.
  - **Attribute-based plugins** (e.g. `#[MigrateSource(id: 'cap_url')]`, `#[InstallTask]`).
  - Constructor property promotion, typed properties, and return types.
- Post-update functions go in `<profile>.post_update.php`; retired ones are listed in
  `hook_removed_post_updates()` with the version they were removed in.
- Install-time setup is done through `InstallTask` plugins under `src/Plugin/InstallTask`.
- PHPUnit tests use PHP attributes (`#[Group('...')]`), not `@group` annotations.
- `CHANGELOG.md` files exist at the root and in each profile.

## Testing

PHPUnit config is `docroot/core/phpunit.xml` (scaffold it with
`drush sws:source:tests:phpunit:config` if missing). Run a single test or directory from the
repo root:

```
vendor/bin/phpunit -c docroot/core docroot/profiles/lagunita/sul_profile/tests/src/Unit
vendor/bin/phpunit -c docroot/core docroot/modules/custom/<module>/tests/src/Unit/...
```

- Full suite: `drush sws:source:tests:phpunit` (add `--with-coverage`). CI runs it against MySQL
  and enforces a coverage threshold with `drush sws:tests:phpunit-coverage-check`, so new code
  needs tests.
- Kernel/functional PHPUnit tests need a database (`docroot/sites/simpletest` must exist).
- Codeception (needs an installed site):
  - `drush codeception --suite=acceptance [--group=<group>] [--test=<name>]` — no JavaScript,
    fast; prefer this suite.
  - `drush codeception --suite=functional` — JavaScript via Selenium/chromedriver; slow.
  - CI runs both suites per site in shards against a fresh `drush drupal:install --site=<site>`,
    with tests from `docroot/profiles/lagunita/<profile>/tests/codeception`.
- CI also runs DB updates against synced databases for each site.

## Local environment

Native setup is in `docs/local-setup.md` (DDEV, Lando, and Gitpod docs are also in `docs/`). In
short: `composer install` → copy `drush/default.local.drush.yml` to `drush/local.drush.yml` →
`drush sws:multisite:settings` → `drush sws:keys` → create `docroot/sites/local.sites.php` →
`drush sws:site:sync --site=<site>`. Most build and ops tooling comes from
`su-sws/sws-drush-commands` (`drush sws:*`).

Local `STANFORD_ENCRYPT` falls back to `salt.txt`; on Acquia it comes from the environment.
Detect environments with `Drupal\SwsDrush\Helpers\EnvironmentDetector`.

## Deployment

- Acquia Cloud hosts all four apps (git remotes in `drush/drush.yml`). The artifact is built into
  `deploy/` by `drush sws:artifact:deploy`, with `drush/deploy-cleanup.sh` stripping dev-only
  files and swapping in `drush/deploy.gitignore`.
- After code deploys, the Acquia hook runs `drush sws:multisite:update:parallel` (database
  updates, config import, and so on for every site).
