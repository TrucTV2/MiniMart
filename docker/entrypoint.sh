#!/bin/sh
set -e

cd /var/www/html

# Chờ database sẵn sàng (tối đa ~60s)
if [ "$DB_CONNECTION" = "mysql" ]; then
    for i in $(seq 1 30); do
        php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}'); exit(0); } catch (Exception \$e) { exit(1); }" && break
        echo "Đang chờ MySQL... ($i)"
        sleep 2
    done
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan optimize          # cache config, route, view, event
php artisan filament:optimize # cache component & icon của Filament

if [ "$SEED_DEMO" = "true" ] && [ "$(php artisan tinker --execute='echo \App\Models\User::count();')" = "0" ]; then
    php artisan db:seed --force
fi

exec "$@"
