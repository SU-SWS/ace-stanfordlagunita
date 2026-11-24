# Dev container for the lagunita multisite: PHP 8.4 (as in CI) with Apache and
# what Drupal and the profiles need. The repository is the site: Apache serves
# its docroot directly, one port per site (see post-start.sh).
FROM mcr.microsoft.com/devcontainers/php:8.4

# PHP extensions CI installs (.github/actions/setup-project), ImageMagick (the
# sites' image toolkit), patch (composer patches and the scaffold patches),
# rsync and unzip (composer, site syncs) and a MySQL client for drush sql-*.
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    default-mysql-client \
    imagemagick \
    libbz2-dev \
    libfreetype-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libmagickwand-dev \
    libpng-dev \
    libzip-dev \
    patch \
    rsync \
    unzip \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" bz2 calendar gd intl mysqli pdo_mysql zip \
  && printf '\n' | pecl install imagick \
  && docker-php-ext-enable imagick \
  && rm -rf /var/lib/apt/lists/*

# The MariaDB client verifies the server's certificate by default, and the
# MySQL container's is self-signed.
RUN mkdir -p /etc/mysql/conf.d \
  && printf '[client]\nskip-ssl-verify-server-cert\n' > /etc/mysql/conf.d/skip-ssl-verify.cnf

# PHP settings for development. Xdebug is installed but only starts when asked
# (XDEBUG_TRIGGER, or a browser extension), so Drush and page loads stay fast.
RUN { \
    echo 'memory_limit = -1'; \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'error_reporting = E_ALL & ~E_DEPRECATED'; \
  } > /usr/local/etc/php/conf.d/zz-lagunita.ini \
  && sed -i 's/^xdebug.start_with_request = yes/xdebug.start_with_request = trigger/' /usr/local/etc/php/conf.d/*xdebug*.ini

# Apache runs as the vscode user, who owns the checkout, so the web server and
# Drush can both write files. The site vhosts are written by post-start.sh,
# because they need the workspace path.
RUN a2enmod rewrite headers expires \
  && sed -i 's/APACHE_RUN_USER:=www-data/APACHE_RUN_USER:=vscode/; s/APACHE_RUN_GROUP:=www-data/APACHE_RUN_GROUP:=vscode/' /etc/apache2/envvars \
  && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
  && a2enconf servername
