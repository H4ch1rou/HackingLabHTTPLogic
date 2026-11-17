#!/bin/sh
# Inicializa la base de datos (idempotente) y arranca Apache.
set -e

mkdir -p /var/www/app/data
php /var/www/app/seed/init.php
chown -R www-data:www-data /var/www/app/data

exec docker-php-entrypoint "$@"
