FROM php:8.2-apache

# Dependencias necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli zip \
    && docker-php-ext-enable mysqli

# Apache modules
RUN a2enmod rewrite headers

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer files
COPY composer.json composer.lock ./

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Copiar proyecto
COPY . .

# Usuario seguro
RUN useradd -ms /bin/bash appuser && \
    chown -R appuser:appuser /var/www/html

USER appuser

EXPOSE 80