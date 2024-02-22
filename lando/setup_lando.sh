#!/usr/bin/env bash

# Three-fingered Claw Technique
yell() { echo "$0: $*" >&2; }
die() { yell "$*"; exit 111; }
try() { "$@" || die "cannot $*"; }

yell "Setting up your site using lando."

try cp lando/example.env .env
try cp lando/example.lando.yml .lando.yml
try cp lando/example.local.blt.yml blt/local.blt.yml
try cp lando/example.php.ini lando/php.ini
try cp lando/example.local.blt.yml blt/local.blt.yml
try cp lando/example.local.sites.php docroot/sites/local.sites.php
try cp lando/example.local.settings.php docroot/sites/default/settings/local.settings.php
try cp lando/sul.local.settings.php docroot/sites/library/settings/local.settings.php
try cp lando/supress.local.settings.php docroot/sites/supress/settings/local.settings.php
try cp docroot/sites/default/settings/default.local.settings.php docroot/sites/default/settings/local.settings.php
try cp lando/codeception.yml tests/codeception.yml

try lando start
try lando composer init-stack
try lando composer sync-sul
try lando composer sync-supress
try lando blt drupal:update --site=library
try lando blt drupal:update --site=supress

echo "Do you wish to install the Library front-end?"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) try mkdir ./frontend-library; try git clone git@github.com:SU-SWS/sulgryphon-nextjs.git ./frontend-library; lando blt gryphon:connect-next-js ./frontend-library http://library.lndo.site; break;;
        No ) break;;
    esac
done

yell "Your site is good to go."
try lando info --format table --filter service=appserver
