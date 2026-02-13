FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libssl-dev \
    pkg-config \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install PHP dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8002

# Create startup script in system location (not overwritten by volume mounts)
RUN printf '#!/bin/bash\n\
cd /var/www/html\n\
# Create .env file from environment variables if it does not exist\n\
if [ ! -f /var/www/html/.env ]; then\n\
  cat > /var/www/html/.env << EOF\n\
APP_NAME=${APP_NAME:-Laravel}\n\
APP_ENV=${APP_ENV:-production}\n\
APP_KEY=\n\
APP_DEBUG=${APP_DEBUG:-false}\n\
APP_URL=${APP_URL:-http://localhost}\n\
DB_CONNECTION=${DB_CONNECTION:-mysql}\n\
DB_HOST=${DB_HOST:-tournament-db}\n\
DB_PORT=${DB_PORT:-3306}\n\
DB_DATABASE=${DB_DATABASE:-tournament_db}\n\
DB_USERNAME=${DB_USERNAME:-root}\n\
DB_PASSWORD=${DB_PASSWORD:-}\n\
EOF\n\
fi\n\
if [ -z "$APP_KEY" ]; then\n\
  php artisan key:generate --force\n\
fi\n\
# Function to run command and always return success\n\
run_safe() {\n\
    "$@" 2>&1 || echo "Command completed with warnings: $*"\n\
    return 0\n\
}\n\
# Run migrations - continue even if some fail (tables may already exist)\n\
run_safe php artisan migrate --force\n\
# Run seeders - continue even if some fail (data may already exist)\n\
run_safe php artisan db:seed --force\n\
# Start the server (this should not exit)\n\
exec php artisan serve --host=0.0.0.0 --port=8002\n' > /usr/local/bin/start-tournament-service.sh && \
chmod +x /usr/local/bin/start-tournament-service.sh

# Run the startup script
CMD ["/usr/local/bin/start-tournament-service.sh"]
