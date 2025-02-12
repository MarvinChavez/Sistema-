# Usa PHP 8.2 con Apache
FROM php:8.2-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    curl unzip git libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libpq-dev libonig-dev libzip-dev libssl-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip gd

# Instala Node.js (versión LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# Habilita mod_rewrite en Apache para Laravel
RUN a2enmod rewrite

# Configura el DocumentRoot de Apache para Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos del proyecto
COPY . .

# Asegura que las carpetas necesarias existan antes de asignar permisos
RUN mkdir -p storage bootstrap/cache && chmod -R 777 storage bootstrap/cache

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-cache --no-plugins --no-scripts

 
# Optimiza la configuración de Laravel
RUN php artisan config:cache
RUN php artisan route:cache

# Instala dependencias de frontend
RUN npm install --no-audit --no-fund

# Compila los assets con Vite
RUN npm run build

# Expone el puerto (Render maneja esto internamente)
EXPOSE 10000

# Inicia Apache
CMD ["apache2-foreground"]

