FROM php:7.2-fpm-alpine as symfony_php

# Install Symfony requirements
RUN \
    apk add --no-cache \
        autoconf \
        automake \
        freetype-dev \
        git \
        icu-libs \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        openssh-client \
        postgresql-libs \
        shadow \
        ssmtp \
        yarn \
        zlib-dev && \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        postgresql-dev && \
    docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
        --with-png-dir=/usr/include/ \
        --with-webp-dir=/usr/include/ && \
    docker-php-ext-install \
        intl \
        gd \
        opcache \
        pdo_pgsql \
        zip && \
    perl -pi -e "s/mailhub=mail/mailhub=maildev/" /etc/ssmtp/ssmtp.conf && \
    apk del .build-deps

# Install Composer globally
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH "${PATH}:/root/.composer/vendor/bin"

# Automatically start the SSH agent when a new session is created
RUN echo 'eval $(ssh-agent) && ssh-add' >> /home/www-data/.profile

# Assign a new UID to avoid using a generated value with docker-sync
RUN usermod -u 1000 www-data

# Install custom entrypoint
COPY entrypoint.sh /usr/local/bin/docker-custom-entrypoint
RUN chmod 777 /usr/local/bin/docker-custom-entrypoint
CMD ["php-fpm"]
ENTRYPOINT ["docker-custom-entrypoint"]

# ======================================================================================================================
FROM symfony_php as symfony_php_blackfire
RUN \
    curl -sS https://packages.blackfire.io/binaries/blackfire-php/1.23.1/blackfire-php-alpine_amd64-php-72.so \
        --output $(php -r "echo ini_get('extension_dir');")/blackfire.so && \
    docker-php-ext-enable blackfire
# ======================================================================================================================

# ======================================================================================================================
FROM symfony_php as symfony_php_xdebug
RUN \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    yes "" | pecl install xdebug && \
    docker-php-ext-enable xdebug
# ======================================================================================================================
