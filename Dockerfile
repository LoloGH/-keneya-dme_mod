# Keneya-DME — image de production.
#
# Trois étapes : dépendances PHP, compilation des assets, image finale.
# Aucun outil de build (Composer, Node) ne subsiste dans l'image servie.

# ---------------------------------------------------------------------
# Étape 1 — dépendances PHP
# ---------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

# Les fichiers de dépendances d'abord : le cache de couche survit à
# toute modification du code applicatif.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---------------------------------------------------------------------
# Étape 2 — compilation des assets
# ---------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Tailwind analyse les vues Blade : elles doivent être présentes avant
# le build, sinon les classes utilitaires sont absentes du CSS produit.
COPY resources ./resources
COPY vite.config.js ./
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# ---------------------------------------------------------------------
# Étape 3 — image finale
# ---------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS app

# Dépendances système : bibliothèques d'images pour GD (photos, QR codes),
# icu pour intl, postgresql/mariadb pour les pilotes de base de données.
RUN apk add --no-cache \
        bash \
        icu-libs \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        oniguruma \
        postgresql-libs \
        fcgi \
        tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && apk del .build-deps

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/keneya.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-keneya.conf

# Code applicatif, dépendances et assets compilés.
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

COPY docker/entrypoint/entrypoint.sh /usr/local/bin/keneya-entrypoint
RUN chmod +x /usr/local/bin/keneya-entrypoint

# Répertoires inscriptibles. Le stockage des documents médicaux est
# volontairement hors de public/ : il n'est jamais servi directement.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/private/medical-documents \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

EXPOSE 9000

# Vérifie que PHP-FPM répond réellement, et pas seulement que le
# processus existe.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD REQUEST_METHOD=GET SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

ENTRYPOINT ["keneya-entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------
# Étape 4 — serveur web
# ---------------------------------------------------------------------
# Nginx embarque public/ et les assets compilés plutôt que de partager
# un volume avec l'application : les fichiers statiques sont ainsi
# versionnés avec l'image, et un redéploiement ne peut pas laisser
# traîner d'anciens assets dans un volume nommé.
FROM nginx:1.27-alpine AS web

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# public/ est nécessaire à nginx : il y résout les fichiers statiques et
# vérifie l'existence de index.php avant de déléguer à PHP-FPM.
COPY public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD wget --spider -q http://127.0.0.1:8080/up || exit 1
