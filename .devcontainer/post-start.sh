#!/usr/bin/env bash
# Runs every time the container starts: secrets, then Apache with one vhost per
# site (ports in site-ports.php), all serving the repository's docroot.
set -euo pipefail

cd "$(dirname "$0")/.."
REPO_DIR="$PWD"

# Optional Codespaces secrets: an SSH key (Acquia, for sws:keys and site
# syncs), and a git config.
if [ -n "${SSH_PRIVATE_KEY:-}" ]; then
  mkdir -p ~/.ssh
  echo "$SSH_PRIVATE_KEY" | base64 -d > ~/.ssh/id_rsa
  chmod 600 ~/.ssh/id_rsa
fi
if [ -n "${GITCONFIG:-}" ]; then
  echo "$GITCONFIG" | base64 -d > ~/.gitconfig
  chmod 644 ~/.gitconfig
fi

{
  printf '<Directory %s/docroot>\n  AllowOverride All\n  Require all granted\n</Directory>\n' "$REPO_DIR"
  while read -r site port; do
    cat <<EOF

Listen $port
<VirtualHost *:$port>
  DocumentRoot $REPO_DIR/docroot
  SetEnv DEVCONTAINER 1
  SetEnv DEVCONTAINER_SITE $site
  ErrorLog \${APACHE_LOG_DIR}/error.log
  CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
  done < <(php -r 'foreach (require $argv[1] as $s => $p) echo "$s $p\n";' .devcontainer/site-ports.php)
  # The key that decrypts encrypted fields in a synced database. Without it,
  # docroot/sites/settings/global.settings.php falls back to salt.txt.
  if [ -n "${STANFORD_ENCRYPT:-}" ]; then
    echo 'PassEnv STANFORD_ENCRYPT'
  fi
} | sudo tee /etc/apache2/sites-available/lagunita.conf > /dev/null
sudo a2ensite -q lagunita > /dev/null

# restart starts Apache if it isn't running.
sudo --preserve-env=STANFORD_ENCRYPT apache2ctl restart
