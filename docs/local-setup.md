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
