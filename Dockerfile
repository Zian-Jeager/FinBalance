# Imagen base con Apache y PHP 8.2
FROM php:8.2-apache

# Instala extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activa mod_rewrite
RUN a2enmod rewrite

# Configura ServerName para evitar warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permite que .htaccess tenga efecto
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copia el código al contenedor
COPY . /var/www/html

# Da permisos (opcional, pero útil si usas logs o subidas)
RUN chown -R www-data:www-data /var/www/html

# Puerto que expone el contenedor (importante para Railway)
EXPOSE 80
