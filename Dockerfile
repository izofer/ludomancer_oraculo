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

# --- 5. EXPANSIÓN DE LA BÓVEDA (Coordenadas Oficiales) ---
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# 6. Traer a Composer (El gestor de paquetes) desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Definir el santuario de trabajo
WORKDIR /var/www/html

# 8. Copiar TODO el código fuente al contenedor
COPY . .

# 9. Instalar las dependencias de PHP (Silencioso y optimizado para producción)
RUN composer install --optimize-autoloader --no-dev

# 10. Otorgar los permisos de escritura sagrados a Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# --- 11. EL PUENTE INMORTAL ---
# Creamos el puente entre la bóveda oscura y la vitrina web después de tener el código y los permisos
RUN php artisan storage:link

# 12. Exponer el puerto 80 para que EasyPanel lo conecte a Internet
EXPOSE 80