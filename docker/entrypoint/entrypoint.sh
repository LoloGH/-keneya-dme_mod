#!/bin/sh
#
# Point d'entrée des conteneurs Keneya-DME.
#
# Prépare l'application avant de céder la place au processus demandé
# (php-fpm, worker de file, planificateur). Idempotent : il peut être
# rejoué à chaque redémarrage sans effet de bord.
#
# Écrit en shell POSIX, sans dépendance à bash : il fonctionne ainsi sur
# n'importe quelle image de base, y compris les variantes minimales.

set -eu

ROLE="${CONTAINER_ROLE:-app}"

log() { printf '[keneya] %s\n' "$1"; }

# ---------------------------------------------------------------------
# Attente de la base de données
# ---------------------------------------------------------------------
wait_for_database() {
    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
        return 0
    fi

    log "Attente de la base de données ${DB_HOST:-db}:${DB_PORT:-3306}…"

    attempt=0
    max_attempts="${DB_WAIT_ATTEMPTS:-60}"

    until php -r '
        $dsn = getenv("DB_CONNECTION") === "pgsql"
            ? sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE"))
            : sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE"));
        try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); }
        catch (Throwable $e) { exit(1); }
    ' 2>/dev/null; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge "$max_attempts" ]; then
            log "La base de données est restée injoignable après ${max_attempts} tentatives."
            exit 1
        fi
        sleep 2
    done

    log "Base de données disponible."
}

# ---------------------------------------------------------------------
# Préparation de l'application
# ---------------------------------------------------------------------
prepare_application() {
    # Une clé applicative absente rend toute session illisible : on la
    # génère plutôt que de démarrer une application inutilisable.
    if [ -z "${APP_KEY:-}" ]; then
        log "APP_KEY absente : génération d'une clé éphémère."
        log "Définissez APP_KEY dans l'environnement pour que les sessions survivent à un redémarrage."
        export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    fi

    # Le stockage des documents reste privé : aucun lien symbolique
    # vers public/ n'est créé.
    # Pas d'expansion d'accolades : ce n'est pas du shell POSIX.
    mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             storage/app/private/medical-documents \
             bootstrap/cache

    # SQLite : le fichier de base n'est pas embarqué dans l'image, il
    # appartient au volume de données. On le crée s'il manque.
    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
        db_path="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
        if [ ! -f "$db_path" ]; then
            log "Création de la base SQLite ${db_path}."
            mkdir -p "$(dirname "$db_path")"
            touch "$db_path"
        fi
    fi

    if [ "${APP_ENV:-production}" = "production" ]; then
        log "Mise en cache de la configuration, des routes et des vues…"
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    else
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
    fi
}

# ---------------------------------------------------------------------
# Migrations
# ---------------------------------------------------------------------
run_migrations() {
    if [ "${RUN_MIGRATIONS:-true}" != "true" ]; then
        log "Migrations désactivées (RUN_MIGRATIONS=false)."
        return 0
    fi

    log "Application des migrations…"
    php artisan migrate --force --no-interaction

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        log "Chargement des données de démonstration…"
        php artisan db:seed --force --no-interaction
    fi
}

# ---------------------------------------------------------------------
# Démarrage selon le rôle du conteneur
# ---------------------------------------------------------------------
main() {
    wait_for_database
    prepare_application

    case "$ROLE" in
        app)
            # Seul le conteneur applicatif migre, pour éviter que
            # plusieurs répliques n'appliquent les migrations en même temps.
            run_migrations
            log "Démarrage de PHP-FPM."
            ;;
        queue)
            log "Démarrage du worker de file d'attente."
            ;;
        scheduler)
            log "Démarrage du planificateur."
            ;;
        *)
            log "Rôle inconnu « ${ROLE} » : démarrage de la commande telle quelle."
            ;;
    esac

    exec "$@"
}

main "$@"
