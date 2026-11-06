
# General Site Architecture
This stack is designed to use a standard Drupal composer build process. This repository contains custom code/themes/etc
but is not intended to contain any contributed modules or drupal core itself. Those are downloaded and commited to a
remote artifact repository stored on Acquia hosting service. To learn more about the mechanism that performs that review the [tools documentation](tools.md)

Each site is split up in a standard "fully decoupled" approach. The backend is managed and maintained by Drupal. Site
administrators will edit content and build out the website using the tools provided by drupal and contributed modules.
The frontend of the site is build with [NextJS](https://nextjs.org/). It uses basic auth to handle authentication with the Drupal
backend to fetch data. At this time, the decoupling is only 1 way: From Drupal to NextJS. There is no implementation or plan
to send data from the front end to Drupal for storage.

# Drupal Config Management
Each site has the ability to determine its own configuration management strategy. The default site in this repo will
be using a configuration management that uses the configuration from the `stanford_profile`. Each site then has a
respective profile which is a git subtree from [`stanford_profile`](https://github.com/SU-SWS/stanford_profile).
By default this is the behavior of all other sites unless defined within their own settings.php & blt.yml files. To pull
updates from the `stanford_profile`, use the following commands for each profile:
- `composer pull-sul`
- `composer pull-press`
- `composer pull-summer`
- `composer pull-csp`

Though there may be merge conflict in the process and will require manual attention. Most often there will be a merge
conflict in the composer.json and the info.yml of each profile. The easiest way to resolve that is `git checkout --ours [path/to/file]`

Notes when running the pull commands:
- Work on a feature branch, not `4.x`. Each `git subtree pull` creates a squash commit plus a merge commit.
- The working tree must be clean before each command. The commands run `rm -rf docroot/profiles/lagunita` and
  `git checkout docroot/profiles/lagunita`, and `git subtree` refuses to run with uncommitted changes. Finish and commit
  each profile's merge (`git commit --no-edit`) before running the next pull command.
- The message `error: remote stanford_profile already exists.` is expected and harmless.
- The commands resolve the composer.json and info.yml conflicts automatically with `--ours`. Any other conflict is left
  unresolved, so check with `git diff --name-only --diff-filter=U` before committing.
- The `csp_profile` and `summer_profile` profiles replace the upstream `CHANGELOG.md` with a one-line heading, so it
  conflicts on every update. Keep the local version: `git checkout --ours docroot/profiles/lagunita/<profile>/CHANGELOG.md`.
- Because the info.yml is always kept as `--ours`, upstream changes to `stanford_profile.info.yml` (like new
  `install` dependencies or themes) are discarded. Compare the upstream info.yml between the old and new squash commits
  and copy any needed changes into each profile's info.yml by hand.

## Add New Sites
To add a new site to the stack run the command `drush lagunita:new-profile`.
This will create the git subtree and rename all files to work with the new profile name.

## Config Splits
Each profile also contains a separate config split that is always enabled. This is used to isolate the custom configuration
and/or changes from `stanford_profile`. Any changes different from the stanford_profile will create a config split patch
file. This works well for most configuration, but some things, like entity displays, don't work well with these patch files.

When exporting configuration, review the files that are exported. It is ideal to get as many configs into the split directory.
It may require editing the "Complete Split" or the "Partial Split" settings to correct the exported file location. Review
one of the existing sites to see examples for how these can be configured.


# Frontend Development
The frontend is build using [NextJS](https://nextjs.org/) and is managed in the each github repository:
- [su-sws/sulgryphon-nextjs](https://github.com/SU-SWS/sulgryphon-nextjs)
- [su-sws/summer-nextjs](https://github.com/SU-SWS/summer-nextjs)
- [su-sws/supress-nextjs](https://github.com/SU-SWS/supress-nextjs)
- [su-sws/csp-nextjs](https://github.com/SU-SWS/csp-nextjs)

See the documentation in those repositories for more information.

# SSL Certificate
A wildcard certificate will be used with a 12 month expiration. This certificate will be updated
automatically via other tools.
