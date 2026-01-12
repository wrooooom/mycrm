# Production-ready Dockerfile for CRM.PROFTRANSFER
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create necessary directories
RUN mkdir -p /var/www/html/logs && \
    mkdir -p /var/www/html/backups && \
    mkdir -p /var/www/html/uploads && \
    chmod -R 755 /var/www/html && \
    chown -R www-data:www-data /var/www/html

# Apache configuration
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-config.ini /usr/local/etc/php/conf.d/app.ini

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Expose port
EXPOSE 80 443

# Start Apache
CMD ["apache2-foreground"]
