#!/bin/sh
set -e

if [ "${APP_ENV:-prod}" = "dev" ]; then
  composer install --no-interaction
  php bin/console cache:clear || true
fi

exec "$@"
