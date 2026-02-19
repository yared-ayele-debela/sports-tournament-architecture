FROM php:8.3-fpm

# Install system dependencies including those needed for Laravel Passport
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libssl-dev \
    pkg-config \
    default-mysql-client \
    libgmp-dev \
    libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip bcmath opcache gmp \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && (pecl install imagick && docker-php-ext-enable imagick || echo "Warning: imagick installation failed, continuing without it")

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create necessary directories and set permissions
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data bootstrap storage \
    && chmod -R 775 bootstrap storage

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions for Passport keys
RUN touch storage/oauth-private.key storage/oauth-public.key \
    && chmod 600 storage/oauth-*.key \
    && chown www-data:www-data storage/oauth-*.key

# Copy and set permissions for entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 8001

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
