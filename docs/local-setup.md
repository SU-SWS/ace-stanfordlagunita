# Setup Local Environment

* [Gitpod](gitpod.md)
* [Lando](lando.md)
* Native lamp stack: see below.

## Native LAMP Stack

BLT provides an automation layer for testing, building, and launching Drupal 8 applications. For ease when updating codebase it is recommended to use  Drupal VM. If you prefer, you can use another tool such as Docker, [DDEV](https://docs.acquia.com/blt/install/alt-env/ddev/), [Docksal](https://docs.acquia.com/blt/install/alt-env/docksal/), [Lando](https://docs.acquia.com/blt/install/alt-env/lando/), (other) Vagrant, or your own custom LAMP stack, however support is very limited for these solutions.

1. After cloning the repository setup blt settings
  * Copy the file `blt/example.local.blt.yml` and name it `local.blt.yml`. Populate all available information with your local configuration values.
2. Install Composer dependencies.
  * After you have cloned the project and setup your local.blt.yml file install Composer Dependencies. (Warning: this can take some time based on internet speeds.)
    ```
    $ composer install
    ```
3. Setup a local blt alias:
  * The easiest is to install it globally 1 time: `composer global require acquia/blt-launcher:*
  * Alternatively, Running BLT using `vendor/bin/blt` is possible.

4. Setup Local settings
After you have the `local.blt.yml` file configured, set up the settings.php for you setup.
    ```
    $ blt blt:init:settings
    ```

Optional:
If you wish to not provide statistics and user information back to Acquia run
     ```
    $ blt blt:telemetry:disable --no-interaction

## Connecting Frontend
The front repository is https://github.com/SU-SWS/sulgryphon-nextjs. Clone the repo into a desired location.
run the command `blt gryphon:connect-nextjs [path-to-frontend-dir] [drupal-backend-domain]`. This command
will create the NextJS entities and configurations as well as establish the NextJS environment variables
necessary to connect to the Drupal backend.
