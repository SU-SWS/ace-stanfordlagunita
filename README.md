# ACE Gryphon

A brief description of My Project.

----
# Provisioning a new site
There are several commands within BLT that make creating a new site much easier.
- `blt recipes:multisite:init` will create a new site directory with all the framework
necessary for a new multisite instance. This command will also ask to create a
new database on Acquia. This can also ran manually by `blt gryphon:create-database`.
The machine name of the site and the database should match. BLT automatically
connects to the database if it has the same name. see https://github.com/acquia/blt/blob/12.x/settings/blt.settings.php#L125
- `blt gryphon:add-domain` can be used to add custom domains to any environment except RA.
- `blt gryphon:add-cert-domain` will add the domain to the LetsEncrypt certificate.
This will only work if the url points to the desired environment.
- `blt gryphon:issue-cert` will request the lets encrypt certificate be renewed.
Note: the letsencrypt_challenge module must be enabled. during this command the
module is enabled for all site aliases for the given environment.
- `blt gryphon:renew-cert` will run a command on Acquia's server to renew the LE certificate.
- `blt gryphon:update-certs` downloads the certificate files from acquia and will
use the cloud API to upload and activate the cert on the acquia environment.

----
# Config Management
Each site has the ability to determine its own configuration management strategy.
The default site in this repo will be using a configuration management that uses
the configuration from the `stanford_profile`. By default this is the behavior
of all other sites unless defined within their own settings.php.

There are three options a site can choose from:
1. Do nothing and the configuration sync directory will use what is in `stanford_profile`.
2. Modify the configuration sync directory to a desired directory such as another profile.
3. Modify the configuration sync directory to point to an empty directory. This
will bypass any configuration management strategy and the site's configuration will be updated via update hooks.

----
# Setup Local Environment - Native LAMP Stack

(See below for Lando config)

BLT provides an automation layer for testing, building, and launching Drupal 8 applications. For ease when updating codebase it is recommended to use  Drupal VM. If you prefer, you can use another tool such as Docker, [DDEV](https://docs.acquia.com/blt/install/alt-env/ddev/), [Docksal](https://docs.acquia.com/blt/install/alt-env/docksal/), [Lando](https://docs.acquia.com/blt/install/alt-env/lando/), (other) Vagrant, or your own custom LAMP stack, however support is very limited for these solutions.
1. Install Composer dependencies.
After you have forked, cloned the project and setup your blt.yml file install Composer Dependencies. (Warning: this can take some time based on internet speeds.)
    ```
    $ composer install
    ```
2. Setup a local blt alias.
If the blt alias is not available use this command outside and inside vagrant (one time only).
    ```
    $ composer run-script blt-alias
    ```
3. Set up local BLT
Copy the file `blt/example.local.blt.yml` and name it `local.blt.yml`. Populate all available information with your local configuration values.

4. Setup Local settings
After you have the `local.blt.yml` file configured, set up the settings.php for you setup.
    ```
    $ blt blt:init:settings
    ```
5. Get secret keys and settings
SAML and other certificate files will be download for local use.
     ```
    $ blt sws:keys
    ```

Optional:
If you wish to not provide statistics and user information back to Acquia run
     ```
    $ blt blt:telemetry:disable --no-interaction
    ```
# Setup Local Environment - Lando

1. Clone this repository somewhere on your local system.
2. Run `composer install --prefer-source`
3. Run `composer init-lando`
4. Run `lando start`
5. Run `lando blt drupal:install -n`

(If using lando, prefix any `blt` commands with `lando`, e.g., `lando blt drupal:install`)

To install Drupal and target one of the sites in the multisite, you can pass the `--site` option, e.g.,

```
lando blt drupal:install -n --site=cardinalservice
```

---
## Other Local Setup Steps

1. Set up frontend build and theme.
By default BLT sets up a site with the lightning profile and a cog base theme. You can choose your own profile before setup in the blt.yml file. If you do choose to use cog, see [Cog's documentation](https://github.com/acquia-pso/cog/blob/8.x-1.x/STARTERKIT/README.md#create-cog-sub-theme) for installation.
See [BLT's Frontend docs](https://docs.acquia.com/blt/developer/frontend/) to see how to automate the theme requirements and frontend tests.
After the initial theme setup you can configure `blt/blt.yml` to install and configure your frontend dependencies with `blt setup`.

2. Pull Files locally.
Use BLT to pull all files down from your Cloud environment.

   ```
   $ blt drupal:sync:files
   ```

3. Sync the Cloud Database.
If you have an existing database you can use BLT to pull down the database from your Cloud environment.
   ```
   $ blt sync
   ```


---

# Resources

Additional [BLT documentation](https://docs.acquia.com/blt/) may be useful. You may also access a list of BLT commands by running this:
```
$ blt
```

Note the following properties of this project:
* Primary development branch: 1.x
* Local environment: @default.local
* Local site URL: http://local.example.loc/

## Working With a BLT Project

BLT projects are designed to instill software development best practices (including git workflows).

Our BLT Developer documentation includes an [example workflow](https://docs.acquia.com/blt/developer/dev-workflow/).

### Important Configuration Files

BLT uses a number of configuration (`.yml` or `.json`) files to define and customize behaviors. Some examples of these are:

* `blt/blt.yml` (formerly blt/project.yml prior to BLT 9.x)
* `blt/local.blt.yml` (local only specific blt configuration)
* `box/config.yml` (if using Drupal VM)
* `drush/sites` (contains Drush aliases for this project)
* `composer.json` (includes required components, including Drupal Modules, for this project)

## Resources

* GitHub - https://github.com/SU-SWS/ace-gryphon
* Acquia Cloud subscription - https://cloud.acquia.com/app/develop/applications/8449683b-500e-4728-b70a-5f69d9e8a61a
