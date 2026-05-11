FROM php:8.2-apache
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN a2enmod rewrite

# Esto copia todas tus carpetas manteniendo la estructura
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
