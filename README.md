# ACE Stanford Lagunita

This repository contains the shared Drupal codebase for multiple decoupled sites and applications. Each site uses its own profile and settings.

Read these before setting up or developing:

* [General Architecture](docs/architecture.md)
* [Local Setup](docs/local-setup.md)
* [Testing](docs/testing.md)

## Sites

| Site               | /sites path | Acquia Application | Profile Name     | Front-End Repo                              |
|--------------------|-------------|--------------------|------------------|---------------------------------------------|
| Continuing studies | `csp`       | `[unknown]`        | `csp_profile`    | https://github.com/SU-SWS/csp-nextjs        |
| Library            | `library`   | `stanfordlagunita` | `sul_profile`    | https://github.com/SU-SWS/sulgryphon-nextjs |
| Summer             | `summer`    | `stanfordsummer`   | `summer_profile` | https://github.com/SU-SWS/summer-nextjs     |
| Press              | `supress`   | `stanfordpress`    | `supress`        | https://github.com/SU-SWS/supress-nextjs    |
