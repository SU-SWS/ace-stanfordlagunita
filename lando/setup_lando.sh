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
try cp docroot/sites/default/settings/default.local.settings.php docroot/sites/default/settings/local.settings.php
try cp lando/codeception.yml tests/codeception.yml

try lando start
try lando composer init-stack

yell "Your site is good to go."
try lando info --format table --filter service=appserver
