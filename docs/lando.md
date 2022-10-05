# Setup Local Environment - Lando

1. Clone this repository somewhere on your local system.
2. Run `composer install --prefer-source`
3. Run `composer init-lando`
4. Run `lando start`
5. Run `lando blt drupal:install -n`

(If using lando, prefix any `blt` commands with `lando`, e.g., `lando blt drupal:install`)

To install Drupal and target one of the sites in the multisite, you can pass the `--site` option, e.g.,

```
lando blt drupal:install -n --site=[sitename]
```