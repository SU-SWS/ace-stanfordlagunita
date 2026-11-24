#!/usr/bin/env bash
# Runs once, when the container is created: the composer build, the local
# settings for every site (docs/local-setup.md, steps 2 to 5) and a database
# for each site. It doesn't install or sync a site; see the message at the end.
#
# Files that already exist (drush/local.drush.yml, the sites' local.settings.php
# and docroot/sites/local.sites.php) are left alone, so a checkout set up for a
# native stack keeps its settings. Delete them and run this again to start over.
set -euo pipefail

cd "$(dirname "$0")/.."
REPO_DIR="$PWD"
DRUSH="$REPO_DIR/vendor/bin/drush"

# A second try, because one failed download from GitHub fails the whole build.
composer install --no-interaction || composer install --no-interaction

# Drush config for the sws:* commands: the database, and the Acquia API key and
# secret for site syncs, from the ACQUIA_KEY and ACQUIA_SECRET Codespaces
# secrets (or environment variables) when they're set.
if [ ! -f drush/local.drush.yml ]; then
  cat > drush/local.drush.yml <<EOF
command:
  sws:
    options:
      db-port: $DB_PORT
      db-host: $DB_HOST
      db-user: $DB_USER
      db-pass: $DB_PASSWORD
      db-name: $DB_NAME
      app-key: ${ACQUIA_KEY:-API-KEY}
      app-secret: ${ACQUIA_SECRET:-API-SECRET}
EOF
  echo "Created drush/local.drush.yml."
fi

# settings.php, local.settings.php and local.drush.yml for every site. The
# options win over drush/local.drush.yml, so the files point at the container's
# database either way.
"$DRUSH" sws:multisite:settings -n \
  --db-host="$DB_HOST" --db-port="$DB_PORT" --db-user="$DB_USER" \
  --db-pass="$DB_PASSWORD" --db-name="$DB_NAME"

# Route the dev container's hostnames to the sites (.devcontainer/sites.php).
include="require __DIR__ . '/../../.devcontainer/sites.php';"
if [ ! -f docroot/sites/local.sites.php ]; then
  printf '<?php\n\n// The dev container'\''s hostnames.\n%s\n' "$include" > docroot/sites/local.sites.php
  echo "Created docroot/sites/local.sites.php."
elif ! grep -qF ".devcontainer/sites.php" docroot/sites/local.sites.php; then
  printf '\n// The dev container'\''s hostnames.\n%s\n' "$include" >> docroot/sites/local.sites.php
  echo "Added the dev container's hostnames to docroot/sites/local.sites.php."
fi

# Each site's database.
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`"
while read -r site _port; do
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}_${site}\`"

  settings="docroot/sites/$site/settings/local.settings.php"
  if [ -f "$settings" ] && ! grep -qF "'host' => '$DB_HOST'" "$settings"; then
    echo "WARNING: $settings doesn't use the $DB_HOST database host. Delete it and run .devcontainer/on-create.sh again."
  fi
done < <(php -r 'foreach (require $argv[1] as $s => $p) echo "$s $p\n";' .devcontainer/site-ports.php)

cat <<'EOF'

The dev container is ready. Each site needs a database before it loads:

  Install a site:  drush drupal:install --site=library -n
  Log in:          drush --uri=http://localhost:8002 uli
  Sync from prod:  drush sws:keys && drush sws:site:sync --site=library
                   (needs an SSH key and an Acquia API key with access to the
                   site: see "Dev container" in docs/local-setup.md)

Sites: csp, library, summer, supress. Their ports: .devcontainer/site-ports.php.
EOF
