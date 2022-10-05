# Provisioning a new site
There are several commands within BLT that make creating a new site much easier.
- `blt recipes:multisite:init` will create a new site directory with all the framework
necessary for a new multisite instance. This command will also ask to create a
new database on Acquia. This can also ran manually by `blt gryphon:create-database`.
The machine name of the site and the database should match. BLT automatically
connects to the database if it has the same name. see https://github.com/acquia/blt/blob/12.x/settings/blt.settings.php#L125
- `blt gryphon:add-domain` can be used to add custom domains to any environment except RA.
- `blt gryphon:create-cron` creates a standard cron job for a specific site that will run every 6 hours.


# Additional Resources

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