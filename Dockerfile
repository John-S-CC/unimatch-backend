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

# Instalar dependencias (Se genera la carpeta vendor)
RUN composer install --no-dev --optimize-autoloader

# Copiar SOLO archivos necesarios
COPY api ./api
COPY servicios ./servicios
COPY configuracion ./configuracion
COPY middleware ./middleware
COPY docs ./docs
COPY openapi.json ./

COPY *.php ./
COPY .htaccess ./

# --- EL CAMBIO ESTÁ AQUÍ ---
# Creamos el usuario seguro y le damos permisos a TODO (incluyendo la carpeta vendor instalada)
RUN useradd -ms /bin/bash appuser && \
    chown -R appuser:appuser /var/www/html

# Ahora sí cambiamos al usuario seguro para correr la app
USER appuser

EXPOSE 80