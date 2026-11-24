# Setup Local Environment

* [Dev container](#dev-container) (VS Code or GitHub Codespaces): see below.
* [Local setup: Mac](mac-local-setup.md)
* Native lamp stack: see below.

## Dev container

`.devcontainer/` runs the codebase in Docker with PHP 8.4, Apache and MySQL 8.0, the same versions CI uses. Open the
repository in VS Code with the Dev Containers extension ("Reopen in Container"), or create a GitHub Codespace from it.

When the container is created, `.devcontainer/on-create.sh` does the native setup steps below for you: `composer
install`, `drush/local.drush.yml`, `drush sws:multisite:settings`, and `docroot/sites/local.sites.php`. It also
creates an empty database for each site. Files that already exist are left alone, so if this checkout was set up for
a native stack, delete its `local.settings.php` files first (the script warns about any that don't point at the
container's database).

Each site is served on its own port, forwarded by VS Code or Codespaces:

| Site    | Port |
|---------|------|
| CSP     | 8001 |
| Library | 8002 |
| Summer  | 8003 |
| Press   | 8004 |

To get links that open in the browser, give Drush the site's port as its URI, for example
`drush --uri=http://localhost:8002 uli` for Library. VS Code opens `localhost` links through its port forwarding, in
Codespaces too. `--uri=library` and `@library.local` still select the site, but their links use `http://library`. SAML
is turned off in the container, so log in with `drush uli` or a local account.

A site needs a database before it loads. Either install it fresh:

```
drush drupal:install --site=library -n
```

or sync it from production, which needs access to Acquia:

```
drush sws:keys
drush sws:site:sync --site=library
```

Syncing needs an SSH key that Acquia accepts and an Acquia API key and secret. In Codespaces, add them as
[Codespaces secrets](https://docs.github.com/en/codespaces/managing-your-codespaces/managing-your-account-specific-secrets-for-github-codespaces)
for this repository, before you create the codespace:

* `SSH_PRIVATE_KEY`: a password-less private key, base64 encoded (`base64 -w 0 < ~/.ssh/id_rsa`).
* `ACQUIA_KEY` and `ACQUIA_SECRET`: written to `drush/local.drush.yml` when the container is created.
* `STANFORD_ENCRYPT`: optional. It's the key for the encrypted fields in a production database. Without it,
  `salt.txt` is used.
* `GITCONFIG`: optional. Your git config, base64 encoded.

Outside Codespaces, edit `app-key` and `app-secret` in `drush/local.drush.yml`, and make your SSH key available in the
container (VS Code forwards your SSH agent).

The database is published on port 33306 of the host (user `root`, with the password from
`.devcontainer/docker-compose.yml`). To start over, delete `drush/local.drush.yml` and the sites' `local.settings.php`
and `local.drush.yml` files, then run `.devcontainer/on-create.sh`.

## Native LAMP Stack

1. Clone the repository locally.
    * `git clone git@github.com:SU-SWS/ace-stanfordlagunita.git`
    * `cd ace-stanfordlagunita`
1. Install Composer dependencies.
    * `composer install`
1. Configure local `drush.yml`.
    * `cp drush/default.local.drush.yml drush/local.drush.yml`
    * Edit the `local.drush.yml`
      * Configure local database settings (these will populate the `local.settings.php` files for the sites).
      * Configure your Acquia API credentials (you can set up these Acquia keys in your Acquia Account).
1. Run multi-site init.
    * `drush sws:multisite:settings`
    * This primarily sets up the configuration and settings files for the sites in `docroot/sites` based on your `local.drush.yml` configuration.
1. Pull keys and secrets locally from Acquia.
    * `drush sws:keys`
1. Set up `local.sites.php`
    * `cp docroot/sites/example.sites.php docroot/sites/local.sites.php`
    * Edit the `local.sites.php`
      * This is used to point local site URL's (e.g., `mysite.test`) to the corresponding site directory. You can use a code loop to the `sites.php` to automatically register domains based on the site directory, such as:
        ```
        // Make sites available locally on lagunita-SITE.test.
        $settings = glob(__DIR__ . '/*/settings.php');
        foreach ($settings as $settings_file) {
          $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
          $site_dir = str_replace('/settings.php', '', $site_dir);
          $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));
          $sites["lagunita-" . $sitename .".test"] = $site_dir;
        }
        ```
1. Setup a vhost (the below instruction are specific to an Apache/LAMP stack):
    * Create and modify the vhost configuration.
      * `cd /etc/apache2/sites-available`
      * `sudo cp 000-default.conf lagunita.conf`
    * The below configuration sets up a wildcard vhost similar to the convention used in the `local.sites.php` (make sure all the paths and conventions match *your* local setup).
      ```
      ServerName lagunita.test
      ServerAlias lagunita-*.test
      DocumentRoot /var/www/html/ace-stanfordlagunita/docroot
      <Directory /var/www/html/ace-stanfordlagunita/docroot >
      ```
    * Enable the vhost and reload apache
      * `sudo a2ensite lagunita`
      * `sudo service apache2 reload`
1. Add the local URL's to your local hosts file.
    * This will be different for Windows vs Linux vs MacOS. You'll want to point any local URL's (using the conventions setup) to your localhost.
    * On Windows with WSL2 using the conventions in this file (`lagunita-*.test`) this looks like:
      ```
      ::1	lagunita-library.test
      ::1	lagunita-summer.test
      # etc.,
      ```
1. Test a site sync locally
    * `drush sws:site:sync --site=<site_alias>`
    * E.g., `drush sws:site:sync --site=summer`
    * The site aliases are `csp`, `library`, `summer` and `supress` (see `drush/sites`).

### Troubleshooting

#### Site sync fails because it needs a database
If the site sync command fails because it cannot find the database, you may need to create it manually:
  * `sudo mysql`
  * `create database stanfordlagunita_<site_alias>;`
  * E.g., `create database stanfordlagunita_summer;`

#### Setting up a new site
If you're syncing a site you have not set up previously, you may need to re-visit some of the steps above like adding the local URL's to your hosts file, creating a mysql database, or running the `drush sws:multisite:settings` command again to populate local settings files.


## Connecting Frontend
Because these are decoupled Drupal applications, each site has their own corresponding front-end respository.
  * https://github.com/SU-SWS/sulgryphon-nextjs
  * https://github.com/SU-SWS/press-nextjs
  * https://github.com/SU-SWS/summer-nextjs
  * https://github.com/SU-SWS/csp-nextjs

The set up instructions will be included in those repositories. The same mapping is kept in `project.frontend-repos`
in `drush/drush.yml`.

Each site's `includes.settings.php` already points the Next site at `http://localhost:3000` on local environments.
A synced database keeps prod's preview and revalidate secrets and API user passwords, so a local front end needs the
same values as prod in its `.env.local`. See [Connecting a Front End](decoupled-frontend.md) for the variables and
for `drush su-next-connect`, which generates new values (and resets the API user passwords).
