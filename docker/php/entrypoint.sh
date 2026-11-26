#!/bin/sh
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env

if [ ! -f vendor/autoload.php ]; then
  echo ">> composer install"
  composer install --no-interaction --prefer-dist
fi

if ! grep -q "^APP_KEY=base64" .env; then
  php artisan key:generate --force
fi

echo ">> aguardando MySQL em ${DB_HOST:-plano-teste-db}..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
  sleep 2
done

php artisan migrate --force
php artisan db:seed --force

echo ">> API em http://localhost:${API_PORT:-8090}/api/planos"
exec php artisan serve --host=0.0.0.0 --port=8000
