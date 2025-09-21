FROM php:8.4-cli

WORKDIR /app

# Install system deps & pdo_mysql
RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev zlib1g-dev libonig-dev unzip git \
 && docker-php-ext-install pdo pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Copy composer from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /app

# Install PHP dependencies (no dev, optimize autoload)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]
