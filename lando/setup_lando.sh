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
try cp lando/codeception.yml tests/codeception.yml

try lando start
try lando composer init-stack
try lando blt drupal:sync --site=library --sync-public-files
try lando blt drupal:sync --site=supress --sync-public-files

echo "Do you wish to install the Library front-end?"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) try mkdir ./frontend-library; try git clone git@github.com:SU-SWS/sulgryphon-nextjs.git ./frontend-library; break;;
        No ) break;;
    esac
done

yell "Your site is good to go."
try lando info --format table --filter service=appserver
