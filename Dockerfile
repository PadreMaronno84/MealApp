FROM php:8.2-apache

# abilito rewrite (non è indispensabile qui, ma ok)
RUN a2enmod rewrite

# permessi: Apache deve poter scrivere in storage/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
