# Usa una imagen base de PHP 8.2 con Apache
FROM php:8.2-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    curl unzip git libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libpq-dev libonig-dev libzip-dev libssl-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip gd

# Habilita mod_rewrite en Apache para Laravel
RUN a2enmod rewrite

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Da permisos correctos a Laravel antes de copiar archivos
RUN mkdir -p storage bootstrap/cache && chmod -R 777 storage bootstrap/cache

# Copia los archivos del proyecto
COPY . .

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Genera la clave de la aplicación
RUN php artisan key:generate

# Optimiza la configuración de Laravel
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Instala dependencias de frontend
RUN npm install --omit=dev

# Configura el entorno para evitar errores con Vite y módulos ESM
ENV NODE_OPTIONS="--experimental-modules"

# Compila los assets con Vite
RUN npm run build

# Exponer puerto 80 (Render lo manejará automáticamente)
EXPOSE 80

# Inicia Apache
CMD ["apache2-foreground"]
