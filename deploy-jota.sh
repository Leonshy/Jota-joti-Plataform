#!/usr/bin/env bash
# Deploy de actualización (el primero se hace a mano: ver docs/despliegue/guia.md).
# Se corre EN EL SERVIDOR, dentro de la carpeta del proyecto:  bash deploy-jota.sh
# No toca el .env ni los datos. Falla y se detiene ante cualquier error.
set -euo pipefail

PHP=/opt/plesk/php/8.3/bin/php
COMPOSER="$PHP /usr/local/psa/var/modules/composer/composer.phar"

cd "$(dirname "$0")"

# Solo avanza si el servidor no tiene cambios locales en el código.
git pull --ff-only

$COMPOSER install --no-dev --optimize-autoloader --no-interaction
$PHP artisan migrate --force

# Reinicia el worker de la cola para que tome el código nuevo, y rehace las cachés.
$PHP artisan queue:restart
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

$PHP artisan jota:verificar-produccion
