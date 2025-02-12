# Usa una imagen base de PHP 8.2 con Apache
FROM php:8.2.4-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    curl unzip git libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libpq-dev libonig-dev libzip-dev libssl-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip gd

# Habilita mod_rewrite en Apache para Laravel
RUN a2enmod rewrite

# Configura Apache para servir desde la carpeta 'public'
COPY ./docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Instala Node.js 20.15.0 y NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Establece el directorio de trabajo
WORKDIR /var/www/html

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
RUN npm install

# Configura el entorno para evitar errores con Vite y módulos ESM
ENV NODE_OPTIONS="--experimental-modules"

# Compila los assets con Vite
RUN npm run build

# Da permisos correctos a Laravel
RUN chmod -R 777 storage bootstrap/cache

# Expone el puerto 10000 (Render lo maneja internamente)
EXPOSE 10000

# Inicia Apache
CMD ["apache2-foreground"]
