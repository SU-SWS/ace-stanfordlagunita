#!/bin/bash

set -ev

mv drush/deploy.gitignore .gitignore
rm -rf .circleci .devcontainer .github .tugboat blt lando patches tests
