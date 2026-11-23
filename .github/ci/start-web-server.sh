#!/usr/bin/env bash
# Serve the site on port 80 with Apache and php-fpm, like production, so
# docroot/.htaccess applies. Run from the repository root.
set -euo pipefail

mkdir -p artifacts
php-fpm -p "$PWD" -y .github/ci/php-fpm.conf

if ! command -v apache2 > /dev/null; then
  sudo apt-get install -y -q --no-install-recommends apache2 \
    || { sudo apt-get update -q && sudo apt-get install -y -q --no-install-recommends apache2; }
fi
sudo cp .github/ci/apache.conf /etc/apache2/sites-available/000-default.conf
# Run as the job's user, who owns the files, and tell apache.conf where the
# project is.
printf 'export %s\n' "APACHE_RUN_USER=$(id -un)" "APACHE_RUN_GROUP=$(id -gn)" \
  "PROJECT_DIR=$PWD" | sudo tee -a /etc/apache2/envvars > /dev/null
sudo a2enmod -q rewrite proxy_fcgi headers expires
# restart starts it if it isn't running yet.
sudo apache2ctl restart

for i in $(seq 1 30); do
  curl -s -o /dev/null http://127.0.0.1/core/misc/druplicon.png && exit 0
  sleep 1
done
echo "The web server didn't start. Its logs:"
tail -n 50 artifacts/apache-error.log artifacts/php-fpm.log
exit 1
