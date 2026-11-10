# AGENTS.md

Guidance for AI coding agents (and humans) working in this repository (`su-sws/ace-stanfordlagunita`).
Human-oriented docs live in `README.md` and `docs/` — read `docs/architecture.md`, `docs/testing.md` and
`docs/decoupled-frontend.md` for more depth. The "Lessons learned" section records traps found in past work.

## Hard rules

- **Never read, grep, diff against, edit, or reference anything in `deploy/`.** It is the build artifact directory
  (`artifact-dir: deploy` in `drush/drush.yml`), produced by `drush sws:artifact:deploy` and pushed to Acquia. It is
  gitignored and is stale/derived — it is not a source of truth, even when it contains a different version of a
  file. Exclude it from searches (`grep -r --exclude-dir=deploy`, `find . -not -path './deploy/*'`).
- **Don't commit secrets.** `keys/`, `files-private/`, `local.*` settings files, and `.env` are gitignored; gitleaks
  runs in CI (`.github/workflows/secret-scan.yml`) and usually in a local pre-commit hook. Read secrets from settings,
  the Key module, or environment variables. See "Secret scanning" below.
- **Don't edit Composer-managed code expecting it to land in this repo** (see "Repository layout"). Change contrib or
  core with a patch in `patches/` referenced from `composer.json` (see `patches/README.md`).
- The main branch is `4.x`. Pushes to `4.x` deploy an artifact automatically; merging a PR can create a
  `4.<minor>.<patch>` release tag. Don't push to `4.x` directly. `CHANGELOG.md` is updated by hand in the release PR.

## What this is

One Drupal 11 (core `~11.4`, PHP ≥ 8.3, CI on PHP 8.4, Drush 13) multisite codebase hosting
**fully decoupled** back ends. Each site has its own install profile; front ends are separate
Next.js repos that read from Drupal (GraphQL, and JSON:API is enabled), with the `next` module calling the front ends
back to revalidate pages and show previews. Content flows one way: Drupal → Next.js.

| Site     | `docroot/sites/` dir | Profile          | Acquia app         | Front-end repo       |
|----------|----------------------|------------------|--------------------|----------------------|
| CSP      | `csp`                | `csp_profile`    | `stanfordgryphon`  | `csp-nextjs`         |
| Library  | `library`            | `sul_profile`    | `stanfordlagunita` | `sulgryphon-nextjs`  |
| Summer   | `summer`             | `summer_profile` | `stanfordsummer`   | `summer-nextjs`      |
| Press    | `supress`            | `supress`        | `stanfordpress`    | `press-nextjs`       |

`default` also exists and uses `stanford_profile`. The multisite list is
`command.sws.options.multisites` in `drush/drush.yml`. See `README.md` for hostnames and the
Akamai/Acquia edge notes.

**BLT is no longer installed.** Tooling is Drush plus `su-sws/sws-drush-commands` (`drush sws:*`). Old docs and
scripts that say `blt ...` are stale; use the Drush equivalents (`drush drupal:install`, `drush drupal:sync`,
`drush sws:site:sync`, `drush codeception`).

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
    namespaces for the profile and helper module (add new test/helper namespaces and the profile's own
    dependencies here)
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
- `.ddev/`, `lando/`, `.gitpod.yml` — optional local environments. All three are out of date and no longer
  documented (see "Stale tooling").

What is **installed by Composer and gitignored** (do not expect changes here to show up in this
repo's `git status`):

- `vendor/`, `docroot/core`, `docroot/modules/contrib`, `docroot/libraries`, `drush/Commands`.
- `docroot/modules/custom/*` and `docroot/profiles/custom/stanford_profile` — Stanford packages
  (`su-sws/stanford_profile_helper`, `stanford_migrate`, `stanford_media`, `stanford_fields`,
  `stanford_samlauth`, `react_paragraphs`, `stanford_actions`, `ckeditor5_plugins`,
  `stanford_profile`). These are installed from source, so **each is its own git checkout** of its
  upstream repo. Edits there must be committed/branched inside that directory (e.g.
  `git -C docroot/modules/custom/stanford_profile_helper status`), and they reach this project
  only by updating `composer.lock`. A `composer install` or `update` can overwrite uncommitted edits there.

Site-specific code belongs in the site's profile: its helper module, its `src/`, and its `config/`.

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
- `next.next_site.*` and `next.next_entity_type_config.*` are config-ignored, so they live in each environment's
  database (see "Front ends").

## Pulling upstream `stanford_profile` changes

```
composer pull-sul | pull-press | pull-summer | pull-csp
```

Each runs `git subtree pull --squash` from `stanford_profile` `13.x` into one profile and keeps
"ours" for the profile's `composer.json` and `.info.yml`. The traps, all documented in
`docs/architecture.md`:

- Work on a feature branch, with a clean tree. Commit each profile's merge before pulling the next.
- `error: remote stanford_profile already exists.` is harmless.
- Only `composer.json` and `<profile>.info.yml` are auto-resolved. Check for other conflicts with
  `git diff --name-only --diff-filter=U`.
- `csp_profile` and `summer_profile` always conflict on `CHANGELOG.md`; keep ours.
- Because the info.yml is always kept as ours, upstream `install:` or theme changes in
  `stanford_profile.info.yml` are silently dropped. Diff the upstream info.yml between the old and
  new squash commits and port the changes to each profile by hand.
- Edits to files that also exist upstream will conflict on the next pull. Keep site-specific changes
  in the helper module, the profile's own files, or its config split.

## Adding a site

Scaffold it with `drush lagunita:new-profile <profile_name> <site_name> <config_prefix>`. Then add the site to
`command.sws.options.multisites` (CI builds its test matrix from that list), `project.frontend-repos`, the Acquia
`git-url` list if it has a new application, the merge-plugin list in `composer.json`, a `pull-*` script, and the
README's Sites table.

## Code conventions

- Drupal coding standards (`phpcs.xml.dist`: `Drupal` + `DrupalPractice`). Run e.g.
  `vendor/bin/phpcs --standard=phpcs.xml.dist <path>`.
- Prefer modern Drupal 11 patterns, which the codebase is actively migrating to (#292, #299, #300):
  - **OOP hooks**: classes in `src/Hook/` with `#[Hook('hook_name')]` methods and constructor
    injection (see `sul_helper/src/Hook/SulHooks.php`). Older procedural hooks still exist in
    `.module` files.
  - **Attribute-based plugins** (e.g. `#[MigrateSource(id: 'cap_url')]`, `#[InstallTask]`), not annotations.
  - Constructor property promotion, typed properties, and return types. Avoid deprecated APIs (prepare for D12).
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
- CI's `DB Updates` job syncs each site's prod database, runs updates, and then clones and builds that site's front
  end (from `project.frontend-repos`) against it.

## Local environment

Native setup is in `docs/local-setup.md` (macOS in `docs/mac-local-setup.md`). In
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

## Lessons learned

### Front ends

The full connection setup (API users, Next site entity, secrets, Vault, Vercel) is in
[`docs/decoupled-frontend.md`](docs/decoupled-frontend.md). Key traps:

- Press's front end is **`press-nextjs`** (`supress-nextjs` does not exist). The mapping CI uses is
  `project.frontend-repos` in `drush/drush.yml`.
- Next site config (`next.next_site.*`, `next.next_entity_type_config.*`) is config-ignored, so it lives in each
  environment's database. A database copy brings prod's secrets with it; `includes.settings.php` only overrides the
  URLs on dev, test and local.
- The Next site machine name must match the key in `docroot/sites/<site>/settings/includes.settings.php`
  (`prod` for CSP, `netlify` for Library, `vercel` for Summer and Press), or the overrides silently don't apply.
- All four front ends preview at `/preview`. Drupal still sends Library, Summer and Press to the legacy
  `/api/draft`, which their front ends redirect to `/preview`. Don't remove that redirect unless Drupal's preview URL
  (Next site entity and `includes.settings.php`) is changed first.
- A failed preview returns the front end's **404**, not a 403 (wrong or missing secret, bad slug).
- `drush su-next-connect` **resets the `nextjs` and `nextjsAdmin` passwords on every run**, defaults to a Next site
  ID of `local`, always sets `/preview`, and (without `--invisible`) switches the default theme and turns on
  `stanford_intranet`. Don't run it against an existing environment unless you intend to rotate credentials and
  redeploy the front end.
- The API users keep their passwords through database syncs because they have the `decoupled_site_users` role, which
  `drush/drush.yml` excludes from `sql:sanitize`. Don't remove that role or that setting.
- Vercel environment variables override Vault values. Vault is only used by CSP and Summer (`vault-envars.ts`), and
  it's read at **build** time through the `env` setting in `next.config.ts`, as are `NEXT_PUBLIC_*` values. So
  changing a value in Vault or Vercel needs a redeploy. Never set `NEXT_PUBLIC_DOMAIN` on non-production (it removes
  the `noindex` header).
- Front-end CI (`build_lint.yml`) checks out this repo's default branch and builds against it. A change here that
  alters the GraphQL schema can break front-end CI.
- Front-end `dev` and `test` branches are rebased onto `1.x` and force-pushed on every push to `1.x`. Their Vercel
  branch URLs (built from the Vercel project and team names) are hard-coded in `includes.settings.php`, so renaming
  either means updating that file.
- When debugging a front end that can't reach Drupal, start with the back-end hostname and whether it is
  Akamai-fronted (README). Library, Press and CSP go through Akamai, so a request can be blocked at the edge and never
  appear in Drupal or Acquia logs. Summer goes straight to Acquia.
- Record infrastructure facts (hostnames, Acquia app names) with the date and source you verified them against, as
  the README does. Undocumented hostnames have slowed incident response before.

### Secret scanning

- `.github/workflows/secret-scan.yml` runs gitleaks with `.gitleaks.toml`. PRs and pushes scan only new commits; a
  weekly run scans the whole history.
- The custom rules catch literals that the default rules miss: a Guzzle `auth` option set to an
  array of literal username and password strings, and password-like keys (`password`, `client_secret`,
  `clientSecret`, `api_key`, `apikey`, ...) assigned a string literal.
- A secret that reached git is in the history: **rotate it**, then remove it from the code.
- A `.gitleaksignore` fingerprint is `<commit>:<file>:<rule>:<line>`. It includes the commit SHA, so a commit that
  exists under different SHAs on different branches (for example, cherry-picked or squashed into `4.x`) needs one
  entry per SHA. Add a comment to every entry explaining why it's safe to ignore.
- The local pre-commit scan (`gitleaks git --pre-commit --staged`) has no commit yet, so its
  fingerprints are `<file>:<rule>:<line>` and never match the commit-based `.gitleaksignore` entries.
  Don't add `<file>:<rule>:<line>` entries to get past it either: the CI scan fingerprints by commit,
  so they won't help there. Fix the flagged line instead.
- Docs and examples trip the rules too. Write credential examples with environment variables
  (`curl -u "$DRUPAL_BASIC_AUTH"`) or describe them in words, not as literal strings.

### Composer

- `composer.lock` is marked `-merge` in `.gitattributes`. On a conflict, take one side and rerun
  `composer update --lock` (or the targeted `composer update <package>`) instead of hand-merging.

### Stale tooling

- `lando/setup_lando.sh` still calls `blt`, which is no longer installed (the Lando docs have been removed).
- `.ddev/config.yaml` still targets `drupal10`, PHP 8.2 and Node 16, which don't match the current stack.
- `.gitpod.yml` remains, but the Gitpod docs have been removed.

Fix these or say so rather than relying on them.
