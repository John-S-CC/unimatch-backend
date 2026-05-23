FROM php:8.2-apache

# Instalar extensiones y herramientas necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli zip \
    && docker-php-ext-enable mysqli

# Habilitar módulos Apache
RUN a2enmod rewrite headers

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de Composer primero
COPY composer.json composer.lock ./

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Copiar el resto del proyecto
COPY . .

# Crear usuario no root para mejorar seguridad
RUN useradd -ms /bin/bash appuser && \
    chown -R appuser:appuser /var/www/html

# Ejecutar contenedor con usuario seguro
USER appuser

EXPOSE 80