# Use an official PHP image with Apache
FROM php:8.2-apache

# Set the working directory
WORKDIR /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl mysqli pdo pdo_mysql

# Configure Apache DocumentRoot
# We will copy the app to /var/www/html/paragliding_booking, so DocumentRoot should be /var/www/html/paragliding_booking/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/paragliding_booking/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/paragliding_booking/public!g' /etc/apache2/apache2.conf || true

# Copy application code
# We'll copy the entire project structure into /var/www/html
COPY ./ /var/www/html/

# Ensure the locale directory is readable by the web server.
# Typically, files copied are owned by root. Apache runs as www-data.
# If Locale.php or other parts of the app need to write here, further permission changes might be needed.
# For now, ensuring readability.
RUN chown -R www-data:www-data /var/www/html/paragliding_booking/locale
RUN chmod -R 755 /var/www/html/paragliding_booking/locale

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
