# Usa la imagen oficial de PHP con Apache
FROM php:8.2.4-apache

# Instala extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip git curl libpq-dev libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip gd

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

# Copia los archivos del proyecto
COPY . /var/www/html

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Da permisos a storage y bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

# Instala Node.js y dependencias de NPM
RUN apt-get install -y nodejs npm && npm install

# Compila los assets con Vite
RUN npm run build

# Expone el puerto 80
EXPOSE 80

# Inicia Laravel con Apache
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
