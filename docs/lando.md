# Setup Local Environment - Lando

1. Clone this repository somewhere on your local system.
2. Enter the directory you cloned into, and run `./lando/setup_lando.sh`
3. After the lando installation completes, the Library and SUPress sites will sync from the remotes.
4. After the sync is complete, drupal will update both sites to reflect the state of the repo.
5. You will be given the option of installing the NextJS frontend for Library. If you choose to do so, it will be installed in the `frontend-library` directory.  If you wish to run the frontend or develop against it, change directories to `<repo root>/frontend-library` and run `nvm use; yarn install; npm run dev;`  That will run the library frontend at `http://localhost:3000`

(If using lando, prefix any `composer`, `drush`, or `blt` commands with `lando`, e.g., `lando blt drupal:install`)

To install fresh Drupal and target one of the sites in the multisite, you can pass the `--site` option, e.g.,

```
lando blt drupal:install -n --site=[sitename]
```
