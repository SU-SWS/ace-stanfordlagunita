# mac-local-setup.md

This guide sets up a local development environment for the ANES, Library  and Summer websites on macOS. Summer is used in many examples.

Local URLs:
- http://anes.lagunita.loc/
- http://library.lagunita.loc/
- http://summer.lagunita.loc/

Prerequisites:
- Install Homebrew:
  `/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"`
- Update Xcode Command Line Tools:
  `xcode-select --install`

Core software installation:
- Install required packages:
  `brew install httpd mysql php composer`
- Install image processing dependencies:
  `brew install pkg-config imagemagick`
- Install patch utility for macOS Ventura+ compatibility:
  `brew install gpatch`
- Install PHP Imagick extension:
  `pecl install imagick`

Environment configuration:
- Update your shell profile (~/.zshrc or ~/.bashrc) and add:
  ```
  export PATH="/opt/homebrew/bin:$PATH"
  export PATH="/opt/homebrew/opt/gpatch/libexec/gnubin:$PATH"`
  alias drush='./vendor/bin/drush'  # add this after cloning the project
  ```
- Apply changes:
  `source ~/.zshrc`

Apache configuration:
- Stop system Apache:
  `sudo apachectl stop`
- Prevent system Apache from starting on boot:
  `sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist`
- Start Homebrew Apache:
  `brew services start httpd`

Configure `/opt/homebrew/etc/httpd/httpd.conf`:
- Add this line near the bottom of the LoadModule section:
  `LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so`
- Set default index files:
  ```
  <IfModule dir_module>
      DirectoryIndex index.php index.html
  </IfModule>
  ```
- Ensure PHP files are handled correctly:
  ```
  <FilesMatch \.php$>
      SetHandler application/x-httpd-php
  </FilesMatch>
  ```
Hosts file (/etc/hosts):
- Add:
```
  127.0.0.1 summer.lagunita.loc lagunita.loc
  127.0.0.1 anes.lagunita.loc lagunita.loc
  127.0.0.1 library.lagunita.loc lagunita.loc
```
Virtual hosts:
- Edit `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`:
  `vim /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`
- Add this (replace YOUR_USERNAME with your macOS username):
```
  <VirtualHost *:80>
    ServerName lagunita.loc
    ServerAlias *.lagunita.loc
    DocumentRoot /Users/YOUR_USERNAME/Sites/ace-stanfordlagunita/docroot
    <Directory "/Users/YOUR_USERNAME/Sites">
      Options FollowSymLinks
      AllowOverride All
      Order allow,deny
      Allow from all
      Require all granted
      Header set Access-Control-Allow-Origin "*"
    </Directory>
  </VirtualHost>
```
MySQL configuration:
- Edit MySQL configuration:
  `vim /opt/homebrew/etc/my.cnf`
- Add:
  ```
  [mysqld]
  max_allowed_packet=1073741824
  ```
- Start MySQL:
  `brew services start mysql`
- Connect to MySQL (set root password if prompted):
  `mysql -u root -p`
- Create the database:
  ```
  CREATE DATABASE lagunita;
  exit
  ```

Project setup:
- Ensure the project exists at:
  ~/[FolderName]/ace-stanfordlagunita
- If needed, install dependencies in the project:
  cd ~/[FolderName]/ace-stanfordlagunita
  `composer install`
- Verify Drush installation:
  `composer show drush/drush`

Fix common issues:
- Increase PHP memory limit:
  `php --ini  # to find the php.ini path`
  `vim /opt/homebrew/etc/php/8.x/php.ini  # replace 8.x with your installed version`
- Change:
  `memory_limit = 512M`

Service management:
- Check service status:
  `brew services list`
- Start, stop, restart services:
  `brew services [start, stop restart] [httpd, php or mysql]`

Access your site:
- Summer local site:
  http://summer.lagunita.loc

Troubleshooting:
- MySQL socket issues:
  `ls -l /tmp/mysql.sock`
- Apache not starting (check logs):
  `tail -f /opt/homebrew/var/log/httpd/error_log`
- PHP module issues:
  - Verify PHP module path in httpd.conf
  - Ensure PHP is properly installed:
    `php -v`
- Permission issues:
  - Ensure your user has read/write access to the project directory
  - Check Apache directory permissions
- Memory issues (e.g., memory exhaustion):
  `php -d memory_limit=1G vendor/bin/drush @summer.local deploy`

Remove incompatible mysql56 module (if present):
- Check if mysql56 module exists:
  `drush @summer.local pm:list | grep mysql56`
- Remove the problematic module:
  ```
  drush @summer.local pmu mysql56 -y
  composer remove drupal/mysql56
  rm -rf docroot/modules/contrib/mysql56  # if not managed by composer
  ```
- Clear cache:
  `drush @summer.local cr`

SSH key troubleshooting:
- Ensure you have the Acquia Cloud key added.
- Check which key you’re using:
  `cat ~/.ssh/id_rsa.pub`
- Quick fix: Add SSH key to agent:
  ```
  eval "$(ssh-agent -s)"
  ssh-add ~/.ssh/id_rsa
  ssh-add -l
  ```
- Then try your drush sql-sync command again.
- Add a new known host (example):
  `ssh stanfordgryphon.prod@stanfordgryphon.ssh.prod.acquia-sites.com`