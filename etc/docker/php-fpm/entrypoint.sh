#!/bin/sh

set -e

if [ "${1#-}" != "$1" ]; then
  set -- php-fpm "$@"
fi

composer clear-cache

#composer install

#composer dump-env localdocker

#echo "generating keypair..."
#php bin/console app:generate:keypair

#echo "Waiting for db..."
#until php bin/console doctrine:query:sql "select 1" > /dev/null 2>&1; do
#  sleep 1
#done

# Doctrine Migrations
# php bin/console doctrine:migrations:migrate -n

# php bin/console app:load:database

touch var/log/supervisord.log

chown -R www-data:www-data var

exec docker-php-entrypoint "$@"
