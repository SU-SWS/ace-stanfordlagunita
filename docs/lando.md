# Setup Local Environment - Lando

1. Clone this repository somewhere on your local system.
2. Enter the directory you cloned into, and run `./lando/setup_lando.sh`
3. After the lando installation completes, the Library and SUPress sites will sync from the remotes.
4. After the sync is complete, drupal will update both sites to reflect the state of the repo.

(If using lando, prefix any `composer`, `drush`, or `blt` commands with `lando`, e.g., `lando blt drupal:install`)

To install Drupal and target one of the sites in the multisite, you can pass the `--site` option, e.g.,

```
lando blt drupal:install -n --site=[sitename]
```
