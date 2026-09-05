#!/usr/bin/env bash
#
# Mise à jour d'une installation Keneya-DME existante.
# Équivalent Windows : scripts\update.ps1

set -euo pipefail

info() { printf '\033[0;36m▸ %s\033[0m\n' "$1"; }

info 'Passage en maintenance…'
php artisan down --render="errors::503" || true

cleanup() {
    php artisan up || true
}
trap cleanup EXIT

info 'Dépendances PHP…'
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

info 'Dépendances JavaScript et assets…'
npm ci
npm run build

info 'Migrations…'
php artisan migrate --force

info 'Reconstruction des caches…'
php artisan config:cache
php artisan route:cache
php artisan view:cache

info 'Redémarrage des workers…'
php artisan queue:restart

printf '\033[0;32m✔ Mise à jour terminée.\033[0m\n'
