# Setup Local Environment

* [Gitpod](gitpod.md)
* [Lando](lando.md)
* [Local setup: Mac](mac-local-setup.md)
* Native lamp stack: see below.

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
  * https://github.com/SU-SWS/anes-nextjs

The set up instructions will be included in those repositories.
