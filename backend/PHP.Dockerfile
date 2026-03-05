FROM php:8.4-fpm

# Install system packages
RUN apt-get update && apt-get install -y \
    ffmpeg \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP upload limits
RUN echo "upload_max_filesize = 110M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 110M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
