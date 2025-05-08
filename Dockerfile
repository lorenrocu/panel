# Usa una imagen base de PHP con Apache
FROM php:8.1-apache

# Instalar dependencias necesarias y extensiones PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    libicu-dev \
    icu-devtools \
    libxml2-dev \
    libxslt-dev \
    zlib1g-dev \
    pkg-config \
    build-essential \
    libssl-dev \
    libzip-dev   # Instalar la librería libzip que falta

# Configurar y activar las extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql intl zip xsl

# Instalar Composer (gestor de dependencias para PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar los archivos del proyecto Laravel al contenedor
COPY . .

# Configurar permisos para directorios de Laravel (storage, cache)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Ejecutar Composer install
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto 80 para Apache
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]
