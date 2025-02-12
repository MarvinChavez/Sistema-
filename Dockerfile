# Usa PHP 8.2 con Apache
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

# Copia los archivos del proyecto antes de instalar dependencias
COPY . .

# Asegura que las carpetas necesarias existan antes de asignar permisos
RUN mkdir -p storage bootstrap/cache && chmod -R 777 storage bootstrap/cache

# Instala Composer sin caché ni scripts problemáticos
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-cache --no-plugins --no-scripts

# Genera la clave de la aplicación
RUN php artisan key:generate

# Optimiza la configuración de Laravel
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Instala dependencias de frontend
RUN npm install --no-audit --no-fund

# Configura el entorno para evitar errores con Vite y módulos ESM
ENV NODE_OPTIONS="--experimental-modules"

# Compila los assets con Vite
RUN npm run build

# Asigna permisos correctos después de instalar dependencias
RUN chmod -R 777 storage bootstrap/cache

# Expone el puerto (Render maneja esto internamente)
EXPOSE 10000

# Inicia Apache
CMD ["apache2-foreground"]
