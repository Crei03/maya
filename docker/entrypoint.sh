#!/bin/sh
set -e

# Default PORT to 80 if not set (Render provides $PORT, e.g. 10000)
export PORT="${PORT:-80}"

echo ">>> Setting Nginx port to ${PORT}..."
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default

# Clean project caches at startup / mount
echo ">>> Clearing Laravel project caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan event:clear || true

# Enforce secure 775 permissions on directories and 664 on files (777 is strictly prohibited)
echo ">>> Enforcing 775 permissions on project folders and subfolders for www-data..."
find /var/www/html/storage /var/www/html/bootstrap/cache -type d -exec chmod 775 {} + 2>/dev/null || true
find /var/www/html/storage /var/www/html/bootstrap/cache -type f -exec chmod 664 {} + 2>/dev/null || true
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Ensure storage link exists
php artisan storage:link --quiet || true

# Optional auto-migrations if RUN_MIGRATIONS=true
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo ">>> Running database migrations..."
    php artisan migrate --force || true
fi

echo ">>> Starting Supervisor (Nginx + PHP-FPM) on port ${PORT}..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
