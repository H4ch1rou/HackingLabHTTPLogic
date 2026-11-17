# HTTP Lab — laboratorio web intencionalmente vulnerable (uso académico).
# Imagen todo-en-uno: Apache + mod_php 8.2 + SQLite (portal + 10 labs + BD).
FROM php:8.2-apache

# Módulos de Apache: rewrite (RC-02), headers (RC-02), autoindex (listado en RC-02).
RUN a2enmod rewrite headers autoindex

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini           /usr/local/etc/php/conf.d/zz-lab.ini
COPY docker/entrypoint.sh     /usr/local/bin/lab-entrypoint

WORKDIR /var/www/app
COPY src    ./src
COPY public ./public
COPY seed   ./seed
# /solutions NO se copia a la imagen: los writeups son solo para el profesor.

RUN sed -i 's/\r$//' /usr/local/bin/lab-entrypoint \
 && chmod +x /usr/local/bin/lab-entrypoint \
 && mkdir -p /var/www/app/data \
 && chown -R www-data:www-data /var/www/app/data

ENV LAB_SECRET=dev-secret-cambiame \
    TEACHER_PASSWORD=profesor \
    TZ=UTC

EXPOSE 80
ENTRYPOINT ["lab-entrypoint"]
CMD ["apache2-foreground"]
