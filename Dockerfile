FROM php:8.2-apache

# Install PostgreSQL extension and other dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers ssl socache_shmcb

# Generate self-signed SSL certificate
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=IN/ST=Telangana/L=Hyderabad/O=IITH/OU=CSE/CN=localhost"

# Custom PHP configuration for security
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Custom Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Create upload directory outside web root
RUN mkdir -p /var/uploads && chown www-data:www-data /var/uploads && chmod 750 /var/uploads

# Copy application code
COPY app/ /var/www/html/

# Copy account creation script
COPY docker/create_accounts.php /var/www/create_accounts.php

# Copy entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80 443

ENTRYPOINT ["entrypoint.sh"]
