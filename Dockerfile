FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libonig-dev libxml2-dev zlib1g-dev curl libwebp-dev msmtp msmtp-mta \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql gd zip intl bcmath opcache exif \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer (copy from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data || true

# Tworzymy strukturę folderów dla uploadu i nadajemy uprawnienia użytkownikowi www-data
RUN mkdir -p upload/photos upload/avatars upload/images \
    && chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
