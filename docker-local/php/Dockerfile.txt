FROM php:8.1-apache

# Paquetes necesarios + extensiones PHP típicas para MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
  && docker-php-ext-install mysqli pdo pdo_mysql \
  && a2enmod rewrite headers \
  && rm -rf /var/lib/apt/lists/*

# Apache: dejamos el docroot por defecto /var/www/html
# El proyecto vive en /var/www/html/admintech (por el volumen)
