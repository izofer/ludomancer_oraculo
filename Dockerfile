# Usamos la imagen oficial de PHP con Apache optimizada
FROM php:8.2-apache

# 1. Instalar dependencias del sistema operativo y extensiones oscuras
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev

# 2. Forjar las extensiones de PHP necesarias para Laravel y MySQL
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Habilitar el motor de reescritura de Apache (Vital para las rutas de Laravel)
RUN a2enmod rewrite

# 4. Inyectar nuestra configuración de Apache
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 5. Traer a Composer (El gestor de paquetes) desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Definir el santuario de trabajo
WORKDIR /var/www/html

# 7. Copiar TODO el código fuente al contenedor
COPY . .

# 8. Instalar las dependencias de PHP (Silencioso y optimizado para producción)
RUN composer install --optimize-autoloader --no-dev

# 9. Otorgar los permisos de escritura sagrados a Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Exponer el puerto 80 para que EasyPanel lo conecte a Internet
EXPOSE 80

# --- EXPANSIÓN DE LA BÓVEDA ---
RUN echo "upload_max_filesize = 100M" > /ruta/correcta/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /ruta/correcta/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /ruta/correcta/php/conf.d/uploads.ini

# Creamos el puente entre la bóveda oscura (storage/app/public) y la vitrina web (public/storage)
RUN php artisan storage:link