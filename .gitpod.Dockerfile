FROM gitpod/workspace-mysql:2024-06-10-10-39-01

RUN sudo update-alternatives --set php $(which php8.2)

RUN sudo apt-get update && sudo apt-get install -y \
    imagemagick \
    libmagickwand-dev \
    libzip-dev \
    keychain \
    php8.2-curl \
    php8.2-gd \
    php8.2-bz2 \
    php8.2-imagick \
    php8.2.xml \
    php8.2.mbstring \
    php8.2-zip \
    php8.2-mysql \
    php-pear \
    rsync \
    zip
RUN sudo pecl install pcov

ENV APACHE_DOCROOT_IN_REPO=docroot

RUN sudo curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN sudo chown gitpod:gitpod -R /home/gitpod/.config
RUN composer global config minimum-stability dev && \
    composer global config prefer-stable true && \
    composer global require drush/drush:^8.0 acquia/blt-launcher:^1.0

ENV PATH="/home/gitpod/.config/composer/vendor/bin:${PATH}"
RUN echo 'export PATH=~/.config/composer/vendor/bin:$PATH' >> ~/.bashrc

USER root
RUN echo 'keychain id_rsa' >> /etc/bash.bashrc
RUN echo '. ~/.keychain/`uname -n` -sh' >> /etc/bash.bashrc

RUN mkdir -p /home/gitpod/.ssh
COPY ssh_config /home/gitpod/.ssh/config

RUN ls /etc/apache2/mods-enabled/