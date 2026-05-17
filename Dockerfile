FROM php:8.2-apache

# Install MariaDB server and client utilities
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL extension for database-driven operations
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for modern URL routing
RUN a2enmod rewrite

# Copy project files to Apache document root
COPY . /var/www/html/

# Set proper permissions for web server access
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80 (standard Apache port, Render will automatically detect and bind to it)
EXPOSE 80

# Copy startup orchestrator script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Run startup script
ENTRYPOINT ["/usr/local/bin/start.sh"]
