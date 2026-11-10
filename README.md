# ACE Stanford Lagunita

This repository contains the shared Drupal codebase for multiple decoupled sites and applications. Each site uses its own profile and settings.

Read these before setting up or developing:

* [General Architecture](docs/architecture.md): decoupling, profiles as `stanford_profile` subtrees, config splits, deployment
* [Local Setup](docs/local-setup.md): native LAMP and [macOS](docs/mac-local-setup.md)
* [Testing](docs/testing.md): PHPUnit, Codeception and CI
* [Connecting a Front End](docs/decoupled-frontend.md): API users, Next module secrets, Vault and Vercel setup
* [AGENTS.md](AGENTS.md): rules and lessons learned for anyone (human or AI agent) changing this repo

## Sites

| Site               | /sites path | Acquia Application | Profile Name     | Back-End Hostname (prod)         | Front-End Repo                              |
|--------------------|-------------|--------------------|------------------|----------------------------------|---------------------------------------------|
| Continuing studies | `csp`       | `stanfordgryphon`  | `csp_profile`    | `edit-csp.stanford.edu`          | https://github.com/SU-SWS/csp-nextjs        |
| Library            | `library`   | `stanfordlagunita` | `sul_profile`    | `library.sites-pro.stanford.edu` | https://github.com/SU-SWS/sulgryphon-nextjs |
| Summer             | `summer`    | `stanfordsummer`   | `summer_profile` | `summer.sites-pro.stanford.edu`  | https://github.com/SU-SWS/summer-nextjs     |
| Press              | `supress`   | `stanfordpress`    | `supress`        | `supress.sites-pro.stanford.edu` | https://github.com/SU-SWS/press-nextjs      |

The back-end hostname is what the decoupled front end calls for GraphQL and JSON:API, and what
you need when reading edge or WAF logs for a site. Verified against the Acquia Cloud API
environment domains on 2026-10-09.

Three of the four are fronted by Akamai; **Summer resolves directly to Acquia**:

| Back-end hostname                | Resolves to                                 |
|----------------------------------|---------------------------------------------|
| `library.sites-pro.stanford.edu` | `stanfordedu.edgesuite.net` (Akamai)        |
| `supress.sites-pro.stanford.edu` | `stanfordedu.edgesuite.net` (Akamai)        |
| `edit-csp.stanford.edu`          | `stanfordedu.edgesuite.net` (Akamai)        |
| `summer.sites-pro.stanford.edu`  | `stanfordsummerprod.prod.acquia-sites.com`  |

That difference matters when debugging: a request to an Akamai-fronted back end can be denied
at the edge (WAF, Client Reputation, bot rules) and never reach Drupal, so it appears in the
Akamai security logs and in **no** application log.

## Common Commands

| Task                                         | Command                                              |
|----------------------------------------------|------------------------------------------------------|
| Generate local settings files for all sites  | `drush sws:multisite:settings`                       |
| Pull keys and secrets from Acquia            | `drush sws:keys`                                     |
| Sync a site from production                  | `drush sws:site:sync --site=<site>`                  |
| Install a site from scratch                  | `drush drupal:install --site=<site>`                 |
| Run PHPUnit tests                            | `drush sws:source:tests:phpunit`                     |
| Run Codeception tests                        | `drush codeception --suite=acceptance\|functional`   |
| Pull `stanford_profile` updates into a profile | `composer pull-sul\|pull-press\|pull-summer\|pull-csp` |
| Create a new site and profile                | `drush lagunita:new-profile`                         |
| Connect a site to a Next.js front end (resets API passwords) | `drush su-next-connect <url> --id=<next site>` |
| Purge one page on the front end              | `drush su-next-invalidate /path`                     |

BLT is no longer part of this stack. Replace any `blt` command you find in older notes with its
Drush equivalent.

## Branches and Releases

`4.x` is the main branch. Pushes to `4.x` build and deploy the artifact to Acquia
(`drush sws:artifact:deploy`). Merging a pull request can tag a release named `4.<minor>.<patch>` (the `Release` workflow picks the next version).
Update `CHANGELOG.md` in the release pull request.

Every pull request and push to `4.x` is also scanned for committed secrets with gitleaks. See
[AGENTS.md](AGENTS.md#secret-scanning).
