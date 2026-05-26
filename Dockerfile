FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    nano \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo \
    pdo_mysql \
    gd \
    zip \
    intl \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN a2enmod rewrite headers expires

RUN echo "short_open_tag=On" > /usr/local/etc/php/conf.d/legacy.ini && \
    echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/legacy.ini && \
    echo "upload_max_filesize=512M" >> /usr/local/etc/php/conf.d/legacy.ini && \
    echo "post_max_size=512M" >> /usr/local/etc/php/conf.d/legacy.ini && \
    echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/legacy.ini && \
    echo "display_errors=On" >> /usr/local/etc/php/conf.d/legacy.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/legacy.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

EXPOSE 80