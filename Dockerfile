FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli zip \
    && docker-php-ext-enable mysqli \
    && a2enmod rewrite headers

WORKDIR /var/www/html

COPY composer.json composer.lock ./
COPY api ./api
COPY servicios ./servicios
COPY configuracion ./configuracion
COPY middleware ./middleware
COPY docs ./docs
COPY openapi.json ./
COPY *.php ./
COPY .htaccess ./

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Limpiamos la caché de Composer interna, instalamos librerías y creamos el usuario seguro
RUN composer clear-cache && \
    composer install --no-dev --optimize-autoloader && \
    useradd -ms /bin/bash appuser && \
    chown -R appuser:appuser /var/www/html

USER appuser

EXPOSE 80