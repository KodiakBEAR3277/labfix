#!/bin/sh
set -e

# Render injects PORT; fall back to 10000 for local `docker run`.
PORT="${PORT:-10000}"
export PORT

# Bake our server block in, expanding only ${PORT}.
envsubst '${PORT}' < /etc/nginx/nginx.conf.custom > /etc/nginx/conf.d/default.conf

cd /var/www/html

# Rebuild caches against the runtime env (env vars are only available now).
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Apply migrations. No storage:link — uploads are served by /storage/{path}.
php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
