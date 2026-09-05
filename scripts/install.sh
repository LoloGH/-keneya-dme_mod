#!/usr/bin/env bash
#
# Installation de Keneya-DME — Linux et macOS.
#
#   ./scripts/install.sh              installation complète
#   ./scripts/install.sh --with-demo  + données de démonstration
#
# Équivalent Windows : scripts\install.ps1
#
# Le script est idempotent : il peut être relancé sans risque.

set -euo pipefail

WITH_DEMO=0
[ "${1:-}" = "--with-demo" ] && WITH_DEMO=1

info()  { printf '\033[0;36m▸ %s\033[0m\n' "$1"; }
ok()    { printf '\033[0;32m✔ %s\033[0m\n' "$1"; }
fail()  { printf '\033[0;31m✖ %s\033[0m\n' "$1" >&2; exit 1; }

# ---------------------------------------------------------------------
# Prérequis
# ---------------------------------------------------------------------
info 'Vérification des prérequis…'

command -v php >/dev/null      || fail 'PHP est introuvable. Installez PHP 8.2 ou supérieur.'
command -v composer >/dev/null || fail 'Composer est introuvable : https://getcomposer.org'
command -v npm >/dev/null      || fail 'Node.js/npm est introuvable : https://nodejs.org'

php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
    || fail "PHP $(php -r 'echo PHP_VERSION;') est trop ancien : 8.2 minimum."

for ext in mbstring openssl tokenizer xml ctype json fileinfo curl; do
    php -m | grep -qi "^${ext}$" || fail "Extension PHP manquante : ${ext}"
done

php -r 'exit(PDO::getAvailableDrivers() ? 0 : 1);' \
    || fail 'Aucun pilote PDO disponible (pdo_mysql, pdo_pgsql ou pdo_sqlite).'

ok "PHP $(php -r 'echo PHP_VERSION;') et dépendances présentes."

# ---------------------------------------------------------------------
# Dépendances
# ---------------------------------------------------------------------
info 'Installation des dépendances PHP…'
composer install --no-interaction --prefer-dist

info 'Installation des dépendances JavaScript…'
npm install

# ---------------------------------------------------------------------
# Environnement
# ---------------------------------------------------------------------
if [ ! -f .env ]; then
    info 'Création du fichier .env…'
    cp .env.example .env
    ok '.env créé à partir de .env.example.'
else
    ok '.env déjà présent : conservé en l’état.'
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    info 'Génération de la clé applicative…'
    php artisan key:generate
fi

# ---------------------------------------------------------------------
# Base de données
# ---------------------------------------------------------------------
DB_CONNECTION=$(grep -E '^DB_CONNECTION=' .env | cut -d= -f2- | tr -d '"' || echo sqlite)

if [ "${DB_CONNECTION}" = "sqlite" ]; then
    if [ ! -f database/database.sqlite ]; then
        info 'Création de la base SQLite…'
        touch database/database.sqlite
    fi
fi

info 'Application des migrations…'
if [ "$WITH_DEMO" = "1" ]; then
    if ! grep -qE '^DEMO_USER_PASSWORD=.+' .env; then
        fail 'DEMO_USER_PASSWORD doit être défini dans .env avant de charger les données de démonstration.'
    fi
    php artisan migrate --seed --force
else
    php artisan migrate --force
fi

# ---------------------------------------------------------------------
# Assets et permissions
# ---------------------------------------------------------------------
info 'Compilation des assets…'
npm run build

info 'Préparation des répertoires inscriptibles…'
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs storage/app/private/medical-documents bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

ok 'Installation terminée.'
echo
echo '  Démarrer le serveur de développement :'
echo '    php artisan serve'
echo
echo '  Traiter la file d’envoi des SMS :'
echo '    php artisan queue:work --queue=sms'
echo
echo '  Vérifier la passerelle SMS :'
echo '    php artisan keneya:sms:check'
echo
[ "$WITH_DEMO" = "1" ] && echo '  Comptes de démonstration : voir README.md'
exit 0
