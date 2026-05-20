FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       pdo \
       pdo_pgsql \
       intl \
       zip \
       opcache \
       gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV GIT_CONFIG_COUNT=1
ENV GIT_CONFIG_KEY_0=safe.directory
ENV GIT_CONFIG_VALUE_0=/var/www/html

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p /var/www/html/vendor \
    && chown -R www-data:www-data /var/www/html

COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

