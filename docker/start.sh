#!/bin/sh
# Prepares a local test copy of Sinala and starts the web server.
set -e
cd /app

[ -f .env ] || cp .env.example .env
[ -f vendor/autoload.php ] || composer install --no-interaction
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

# "artisan serve" ignores most container variables, so copy them into .env.
for key in APP_ENV APP_DEBUG APP_URL DB_CONNECTION DB_DATABASE; do
  value=$(printenv "$key" || true)
  [ -n "$value" ] || continue
  if grep -qE "^#? ?$key=" .env; then
    sed -i -E "s|^#? ?$key=.*|$key=$value|" .env
  else
    echo "$key=$value" >> .env
  fi
done

touch "$DB_DATABASE"
php artisan migrate --force

# Sample data (CIES organization, users and an event) only on the first start.
if [ ! -f storage/app/.docker-seeded ]; then
  php artisan db:seed --force
  touch storage/app/.docker-seeded
fi

php artisan optimize:clear >/dev/null
echo "Sinala is running at http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
