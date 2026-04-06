#!/bin/sh
set -e

echo "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "Waiting for database..."
php artisan db:wait 2>/dev/null || true

echo "Running migrations..."
php artisan migrate --force

echo "Starting Vite dev server..."
npm run dev &

echo "Starting Laravel..."
exec php artisan serve --host=0.0.0.0 --port=8000
