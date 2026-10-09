# ACE Stanford Lagunita

This repository contains the shared Drupal codebase for multiple decoupled sites and applications. Each site uses its own profile and settings.

Read these before setting up or developing:

* [General Architecture](docs/architecture.md)
* [Local Setup](docs/local-setup.md)
* [Testing](docs/testing.md)

## Sites

| Site               | /sites path | Acquia Application | Profile Name     | Back-End Hostname (prod)         | Front-End Repo                              |
|--------------------|-------------|--------------------|------------------|----------------------------------|---------------------------------------------|
| Continuing studies | `csp`       | `stanfordgryphon`  | `csp_profile`    | `edit-csp.stanford.edu`          | https://github.com/SU-SWS/csp-nextjs        |
| Library            | `library`   | `stanfordlagunita` | `sul_profile`    | `library.sites-pro.stanford.edu` | https://github.com/SU-SWS/sulgryphon-nextjs |
| Summer             | `summer`    | `stanfordsummer`   | `summer_profile` | `summer.sites-pro.stanford.edu`  | https://github.com/SU-SWS/summer-nextjs     |
| Press              | `supress`   | `stanfordpress`    | `supress`        | `supress.sites-pro.stanford.edu` | https://github.com/SU-SWS/supress-nextjs    |

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
