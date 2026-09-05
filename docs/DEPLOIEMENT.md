# Guide de déploiement — Keneya-DME

Ce guide couvre l'installation et l'exploitation de Keneya-DME sur les
sept configurations supportées.

| # | Plateforme | Section |
|---|---|---|
| 1 | Linux natif | [B](#b--installation-linux-native) |
| 2 | Linux + Apache | [D](#d--apache) |
| 3 | Linux + Nginx | [E](#e--nginx) |
| 4 | Linux + Docker | [F](#f--docker) |
| 5 | Windows natif | [C](#c--installation-windows) |
| 6 | Windows + Apache | [C.4](#c4--apache-sous-windows) |
| 7 | Windows + Docker Desktop | [F.6](#f6--docker-desktop-windows) |

> **Docker est une option de déploiement, pas une dépendance.**
> L'application fonctionne parfaitement sans lui.

---

## Sommaire

- [A. Prérequis](#a--prérequis)
- [B. Installation Linux native](#b--installation-linux-native)
- [C. Installation Windows](#c--installation-windows)
- [D. Apache](#d--apache)
- [E. Nginx](#e--nginx)
- [F. Docker](#f--docker)
- [G. Base de données](#g--base-de-données)
- [H. Configuration SMSGate](#h--configuration-smsgate)
- [I. Worker de file d'attente](#i--worker-de-file-dattente)
- [J. Stockage](#j--stockage)
- [K. Planificateur](#k--planificateur)
- [L. Tests](#l--tests)
- [M. Build de production](#m--build-de-production)
- [N. Dépannage](#n--dépannage)
- [O. Sécurité](#o--sécurité)
- [P. Mise à jour](#p--mise-à-jour)

---

## A — Prérequis

### Communs à toutes les plateformes

| Composant | Version | Remarque |
|---|---|---|
| PHP | **8.2 minimum**, 8.4 recommandé | |
| Composer | 2.x | |
| Node.js / npm | 20 minimum, 22 recommandé | Uniquement pour compiler les assets |
| Base de données | MySQL 8.0+, MariaDB 10.6+, PostgreSQL 14+ ou SQLite 3.35+ | |

### Extensions PHP requises

```text
mbstring  openssl  tokenizer  xml  ctype  json  fileinfo  curl
pdo  +  pdo_mysql | pdo_pgsql | pdo_sqlite   (selon le moteur retenu)
```

### Extensions recommandées

| Extension | Utilité |
|---|---|
| `gd` | Traitement d'images |
| `intl` | Formatage local des dates et nombres |
| `zip` | Import/export d'archives |
| `bcmath` | Calculs de précision |
| `opcache` | **Indispensable en production** |
| `pcntl` | Arrêt propre des workers (Linux uniquement) |

Vérifier l'ensemble en une commande :

```bash
php -m
php artisan keneya:sms:check   # après installation
```

---

## B — Installation Linux native

### B.1 — Dépendances système

**Debian / Ubuntu**

```bash
sudo apt update
sudo apt install -y php8.4-cli php8.4-fpm php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-mysql php8.4-pgsql php8.4-sqlite3 php8.4-gd \
    php8.4-intl php8.4-zip php8.4-bcmath \
    composer nodejs npm git unzip
```

**RHEL / Rocky / AlmaLinux**

```bash
sudo dnf install -y php-cli php-fpm php-mbstring php-xml php-pdo \
    php-mysqlnd php-pgsql php-gd php-intl php-zip php-bcmath \
    composer nodejs npm git unzip
```

### B.2 — Installation

```bash
git clone https://github.com/LoloGH/keneya-dme_app.git /var/www/keneya-dme
cd /var/www/keneya-dme

./scripts/install.sh              # installation
# ou :
./scripts/install.sh --with-demo  # + données de démonstration
```

Le script vérifie les prérequis, installe les dépendances, crée `.env`,
génère la clé applicative, applique les migrations et compile les assets.

### B.3 — Installation manuelle (équivalent)

```bash
composer install --no-interaction --prefer-dist
npm install

cp .env.example .env
php artisan key:generate

# SQLite uniquement :
touch database/database.sqlite

php artisan migrate --force
npm run build
```

### B.4 — Permissions

L'utilisateur du serveur web doit pouvoir écrire dans deux répertoires,
et **seulement** ces deux-là :

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### B.5 — Démarrage

```bash
# Développement
php artisan serve                 # http://127.0.0.1:8000

# Production : voir les sections Apache (D) ou Nginx (E)
```

---

## C — Installation Windows

### C.1 — Dépendances

1. **PHP** — [windows.php.net/download](https://windows.php.net/download/)
   (version *Thread Safe* si Apache, *Non Thread Safe* si IIS/FastCGI).
   Décompresser dans `C:\php` et ajouter ce dossier au `PATH`.

2. Copier `php.ini-production` vers `php.ini`, puis activer :

   ```ini
   extension_dir = "ext"

   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=pdo_pgsql
   extension=pdo_sqlite
   extension=zip

   zend_extension=opcache
   ```

3. **Composer** — [getcomposer.org/Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
4. **Node.js LTS** — [nodejs.org](https://nodejs.org/)
5. **Git** — [git-scm.com/download/win](https://git-scm.com/download/win)

Vérification dans PowerShell :

```powershell
php --version
composer --version
node --version
```

### C.2 — Installation

```powershell
git clone https://github.com/LoloGH/keneya-dme_app.git C:\keneya-dme
cd C:\keneya-dme

# Si l'exécution de scripts est bloquée :
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\install.ps1
# ou :
.\scripts\install.ps1 -WithDemo
```

### C.3 — Installation manuelle (équivalent)

```powershell
composer install --no-interaction --prefer-dist
npm install

Copy-Item .env.example .env
php artisan key:generate

# SQLite uniquement :
New-Item -ItemType File database\database.sqlite -Force

php artisan migrate --force
npm run build

php artisan serve
```

### C.4 — Apache sous Windows

Avec **XAMPP**, **Laragon** ou une installation Apache autonome, ajouter
dans `httpd-vhosts.conf` :

```apache
<VirtualHost *:80>
    ServerName keneya-dme.local
    DocumentRoot "C:/keneya-dme/public"

    <Directory "C:/keneya-dme/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Aucun autre répertoire ne doit être servi.
    <DirectoryMatch "C:/keneya-dme/(app|bootstrap|config|database|storage|vendor)">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
```

Puis ajouter dans `C:\Windows\System32\drivers\etc\hosts` :

```text
127.0.0.1  keneya-dme.local
```

Activer `mod_rewrite` et `mod_headers` dans `httpd.conf`, puis redémarrer
Apache.

### C.5 — Permissions Windows

Donner le contrôle total sur `storage` et `bootstrap\cache` au compte du
service web (`IUSR` ou le compte du pool d'applications) :

```powershell
icacls "C:\keneya-dme\storage" /grant "IUSR:(OI)(CI)F" /T
icacls "C:\keneya-dme\bootstrap\cache" /grant "IUSR:(OI)(CI)F" /T
```

### C.6 — Worker et planificateur sous Windows

Voir [I.3](#i3--windows) et [K.2](#k2--windows) : le worker et le
planificateur s'installent en **tâches planifiées Windows**, aucun script
Bash n'est nécessaire.

---

## D — Apache

Un fichier prêt à l'emploi est fourni :

```bash
sudo cp deploy/apache/keneya-dme.conf /etc/apache2/sites-available/
sudo a2enmod rewrite headers deflate expires proxy_fcgi setenvif
sudo a2enconf php8.4-fpm
sudo a2ensite keneya-dme
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Adapter avant activation :

- `ServerName`
- le chemin d'installation (`/var/www/keneya-dme`)
- la socket PHP-FPM (`/run/php/php8.4-fpm.sock`)

### Points de sécurité vérifiés par la configuration fournie

| Point | Mise en œuvre |
|---|---|
| `DocumentRoot` | `public/` **uniquement** |
| `.env`, `composer.json`, `*.sqlite` | refusés par `FilesMatch` |
| `app/`, `storage/`, `vendor/`, `database/` | refusés par `DirectoryMatch` |
| Listing de répertoires | `Options -Indexes` |
| En-têtes de sécurité | `mod_headers` |

Le fichier `public/.htaccess` livré applique les mêmes en-têtes, ce qui
protège aussi les hébergements mutualisés sans accès au VirtualHost.

---

## E — Nginx

```bash
sudo cp deploy/nginx/keneya-dme.conf /etc/nginx/sites-available/keneya-dme
sudo ln -s /etc/nginx/sites-available/keneya-dme /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Adapter `server_name`, `root` et `fastcgi_pass`.

La configuration fournie :

- sert `public/` et rien d'autre ;
- refuse tout fichier caché (`location ~ /\.`) ;
- interdit l'exécution de PHP depuis `storage/`, `vendor/`, `database/`
  et `bootstrap/` ;
- pose les en-têtes de sécurité ;
- met en cache les assets Vite pour un an (leur nom porte une empreinte) ;
- porte `fastcgi_read_timeout` à 120 s pour la génération de PDF.

---

## F — Docker

### F.1 — Composition de la stack

```text
        Internet
           │
           ▼
    web  (Nginx, port 8080)
           │  FastCGI
           ▼
    app  (PHP-FPM)  ──────┬──────────────┐
           │              │              │
           ▼              ▼              ▼
    db (MySQL 8.4)   queue (worker)  scheduler
```

| Service | Rôle |
|---|---|
| `web` | Nginx — sert les assets, délègue PHP à `app` |
| `app` | PHP-FPM — applique les migrations au démarrage |
| `queue` | Worker de file : SMS et notifications |
| `scheduler` | Suivi d'acheminement SMS, purges |
| `db` | MySQL 8.4 |

### F.2 — Démarrage

```bash
cp .env.docker.example .env

# Générer la clé applicative et la reporter dans .env :
docker compose run --rm app php artisan key:generate --show

# Renseigner obligatoirement DB_PASSWORD et DB_ROOT_PASSWORD dans .env.
# La stack refuse de démarrer sans ces valeurs.

docker compose up -d --build
```

L'application est disponible sur <http://localhost:8080>.

### F.3 — Commandes courantes

```bash
docker compose ps                       # état des services
docker compose logs -f app              # journaux applicatifs
docker compose exec app php artisan migrate:status
docker compose exec app php artisan keneya:sms:check
docker compose down                     # arrêt
docker compose down -v                  # arrêt + suppression des données
```

### F.4 — Variables de pilotage

| Variable | Défaut | Effet |
|---|---|---|
| `APP_PORT` | `8080` | Port publié par `web` |
| `RUN_MIGRATIONS` | `true` | Migrations au démarrage de `app` |
| `RUN_SEEDERS` | `false` | Données de démonstration |
| `DB_WAIT_ATTEMPTS` | `60` | Tentatives d'attente de la base |

Seul le conteneur `app` applique les migrations : `queue` et `scheduler`
démarrent avec `RUN_MIGRATIONS=false`, ce qui évite toute exécution
concurrente.

### F.5 — Volumes

| Volume | Contenu | Sauvegarde |
|---|---|---|
| `database` | Données MySQL | **Oui** |
| `documents` | Documents médicaux | **Oui** |
| `logs` | Journaux applicatifs | Selon politique |

Le code applicatif vient de l'image, jamais de l'hôte : un redéploiement
consiste à reconstruire l'image, pas à modifier un volume.

### F.6 — Docker Desktop (Windows)

```powershell
Copy-Item .env.docker.example .env
# Renseigner APP_KEY, DB_PASSWORD et DB_ROOT_PASSWORD dans .env

docker compose up -d --build
Start-Process "http://localhost:8080"
```

Points d'attention sous Windows :

- activer l'intégration WSL 2 dans Docker Desktop ;
- placer le dépôt dans le système de fichiers WSL (`\\wsl$\...`) plutôt
  que sur `C:\` : les performances d'E/S sont sans commune mesure ;
- les fins de ligne sont gérées par `.gitattributes` (les scripts shell
  restent en LF, les `.ps1` en CRLF).

### F.7 — Passer à PostgreSQL

Remplacer le service `db` de `docker-compose.yml` :

```yaml
  db:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: "${DB_DATABASE:-keneya_dme}"
      POSTGRES_USER: "${DB_USERNAME:-keneya}"
      POSTGRES_PASSWORD: "${DB_PASSWORD:?DB_PASSWORD doit être défini}"
    volumes:
      - database:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-keneya}"]
      interval: 10s
      timeout: 5s
      retries: 10
    networks:
      - keneya
```

puis, dans `.env` : `DB_CONNECTION=pgsql` et `DB_PORT=5432`.

---

## G — Base de données

### G.1 — Moteurs supportés

| Moteur | Statut | Usage recommandé |
|---|---|---|
| **MySQL 8.4** | Vérifié — migrations, seeders et suite de tests complète | Production |
| **PostgreSQL 16** | Vérifié — migrations, seeders et suite de tests complète | Production |
| MariaDB 10.6+ | Compatible (protocole MySQL) | Production |
| SQLite | Vérifié | Développement, démonstration, tests |

### G.2 — MySQL / MariaDB

```sql
CREATE DATABASE keneya_dme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'keneya'@'localhost' IDENTIFIED BY 'mot_de_passe_robuste';
GRANT ALL PRIVILEGES ON keneya_dme.* TO 'keneya'@'localhost';
FLUSH PRIVILEGES;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=keneya_dme
DB_USERNAME=keneya
DB_PASSWORD=mot_de_passe_robuste
```

### G.3 — PostgreSQL

```sql
CREATE DATABASE keneya_dme ENCODING 'UTF8';
CREATE USER keneya WITH ENCRYPTED PASSWORD 'mot_de_passe_robuste';
GRANT ALL PRIVILEGES ON DATABASE keneya_dme TO keneya;
\c keneya_dme
GRANT ALL ON SCHEMA public TO keneya;
```

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=keneya_dme
DB_USERNAME=keneya
DB_PASSWORD=mot_de_passe_robuste
```

### G.4 — SQLite

```dotenv
DB_CONNECTION=sqlite
# DB_DATABASE peut rester vide : database/database.sqlite par défaut.
```

SQLite convient au développement, à la démonstration et aux tests. Pour
un établissement en exploitation réelle, préférer MySQL ou PostgreSQL :
les écritures concurrentes y sont mieux gérées.

### G.5 — Sauvegarde

```bash
# MySQL
mysqldump -u keneya -p --single-transaction keneya_dme > sauvegarde.sql

# PostgreSQL
pg_dump -U keneya -Fc keneya_dme > sauvegarde.dump

# Docker
docker compose exec db mysqldump -u root -p --single-transaction keneya_dme > sauvegarde.sql
```

**Sauvegarder aussi `storage/app/private/`** : les documents médicaux ne
sont pas dans la base.

---

## H — Configuration SMSGate

SMSGate ([sms-gate.app](https://sms-gate.app)) est la passerelle SMS de
production du projet.

### H.1 — Les deux modes d'exploitation

| Mode | `SMSGATE_BASE_URL` | Usage |
|---|---|---|
| **Cloud** | `https://api.sms-gate.app/3rdparty/v1` | L'appareil Android n'est pas joignable depuis le serveur |
| **Local** | `http://<ip-appareil>:8080/3rdparty/v1` | Appareil sur le réseau de l'établissement — aucune donnée ne sort |

Le mode local est préférable en établissement : les numéros de patients
ne transitent alors par aucun service tiers.

### H.2 — Configuration

```dotenv
SMS_GATEWAY=smsgate

SMSGATE_BASE_URL=https://api.sms-gate.app/3rdparty/v1
SMSGATE_USERNAME=votre_identifiant
SMSGATE_PASSWORD=votre_mot_de_passe

# Variante par jeton :
# SMSGATE_TOKEN=votre_jeton

SMSGATE_SIM_NUMBER=            # 1 ou 2 ; vide = choix de l'appareil
SMSGATE_DELIVERY_REPORT=true   # accusés de remise
SMSGATE_TIMEOUT=15
SMSGATE_VERIFY_TLS=true        # false uniquement en local avec certificat auto-signé
```

### H.3 — Vérification

```bash
php artisan keneya:sms:check
```

La commande affiche la passerelle active, la présence des identifiants
(**jamais leur valeur**) et interroge le point `/health`.

### H.4 — Cycle de vie d'un message

```text
pending → queued → accepted → sent → delivered
                       │        │
                       └────────┴──→ failed
```

| État | Signification |
|---|---|
| `pending` | Enregistré, pas encore mis en file |
| `queued` | Dans la file d'attente applicative |
| `accepted` | **SMSGate a accusé réception — le message n'est pas encore parti** |
| `sent` | SMSGate confirme l'émission vers l'opérateur |
| `delivered` | Accusé de remise reçu |
| `failed` | Échec définitif |

> L'application ne présente **jamais** un message comme envoyé tant que
> la passerelle ne l'a pas confirmé. La progression `accepted → sent →
> delivered` est assurée par le planificateur (section K).

### H.5 — Mode développement

```dotenv
SMS_GATEWAY=log
```

Aucun SMS réel n'est émis ; les messages sont journalisés. L'écran SMS
affiche alors un bandeau d'avertissement explicite.

### H.6 — Sécurité

- Les identifiants ne figurent **que** dans `.env`, jamais dans le dépôt.
- Les messages d'erreur sont expurgés : un mot de passe apparaissant dans
  une URL est remplacé par `***` avant journalisation ou affichage.
- Aucun résultat clinique n'est transmis par SMS : le réseau mobile n'est
  pas maîtrisé.

---

## I — Worker de file d'attente

Les SMS et les notifications transitent par une file : l'indisponibilité
d'une passerelle ne bloque jamais un acte médical.

### I.1 — Développement

```bash
php artisan queue:work --queue=sms,default
```

### I.2 — Production Linux (systemd)

```bash
sudo cp deploy/systemd/keneya-dme-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now keneya-dme-queue
sudo systemctl status keneya-dme-queue
```

Alternative avec Supervisor :

```ini
[program:keneya-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/keneya-dme/artisan queue:work --queue=sms,default --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/keneya-queue.log
stopwaitsecs=3600
```

### I.3 — Windows

Créer une tâche planifiée exécutée au démarrage :

```powershell
$action  = New-ScheduledTaskAction -Execute 'php.exe' `
    -Argument 'artisan queue:work --queue=sms,default --tries=3 --max-time=3600' `
    -WorkingDirectory 'C:\keneya-dme'

$trigger = New-ScheduledTaskTrigger -AtStartup

$settings = New-ScheduledTaskSettingsSet -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask -TaskName 'Keneya-DME Queue' `
    -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest
```

### I.4 — Docker

Le service `queue` s'en charge ; aucune action n'est requise.

### I.5 — Après chaque déploiement

```bash
php artisan queue:restart
```

Les workers chargent le code en mémoire : sans cette commande, ils
continuent d'exécuter l'ancienne version.

---

## J — Stockage

### J.1 — Principe

Les documents médicaux sont stockés **hors de `public/`**, sur le disque
privé `storage/app/private/medical-documents/`.

Aucun lien symbolique vers `public/` n'est créé — c'est délibéré. Tout
accès passe par une route contrôlée qui vérifie la permission de
l'utilisateur et inscrit l'extraction au journal d'audit.

> **Ne jamais exécuter `php artisan storage:link`** sur cette
> application : cela exposerait les documents médicaux par URL directe.

### J.2 — Arborescence

```text
storage/
├── app/
│   └── private/
│       └── medical-documents/<id-patient>/<uuid>.<ext>
├── framework/{cache,sessions,views}
└── logs/
```

Le nom de fichier est un UUID : connaître l'identifiant d'un patient ne
permet pas de deviner l'URL d'un document.

### J.3 — Sauvegarde

```bash
tar czf documents-$(date +%F).tar.gz storage/app/private/

# Docker
docker run --rm -v keneya_documents:/data -v "$PWD:/backup" \
    alpine tar czf /backup/documents-$(date +%F).tar.gz -C /data .
```

---

## K — Planificateur

Le planificateur assure le suivi d'acheminement des SMS et les purges.

### K.1 — Linux

**Option 1 — cron (une seule entrée) :**

```bash
crontab -e -u www-data
```

```cron
* * * * * cd /var/www/keneya-dme && php artisan schedule:run >> /dev/null 2>&1
```

**Option 2 — systemd (recommandé, journalisé) :**

```bash
sudo cp deploy/systemd/keneya-dme-scheduler.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now keneya-dme-scheduler
```

### K.2 — Windows

```powershell
$action = New-ScheduledTaskAction -Execute 'php.exe' `
    -Argument 'artisan schedule:run' -WorkingDirectory 'C:\keneya-dme'

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask -TaskName 'Keneya-DME Scheduler' `
    -Action $action -Trigger $trigger -RunLevel Highest
```

### K.3 — Tâches planifiées

| Tâche | Fréquence | Rôle |
|---|---|---|
| `keneya:sms:refresh` | 5 minutes | Fait progresser les SMS `accepted → sent → delivered` |
| `queue:prune-failed` | quotidienne | Purge les jobs en échec de plus de 7 jours |

```bash
php artisan schedule:list   # vérifier la planification
```

---

## L — Tests

```bash
php artisan test                      # suite complète
php artisan test --testsuite=Unit     # tests unitaires
php artisan test --filter=Security    # tests de sécurité
php artisan test --filter=SmsGate     # passerelle SMSGate
```

> Utiliser `php artisan test` sans `--env=testing` : ce drapeau réactive
> la protection CSRF, que le harnais de test contourne normalement, et
> fait échouer les tests d'envoi de formulaire.

### Tester sur un autre moteur

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_DATABASE=keneya_dme DB_USERNAME=keneya DB_PASSWORD=… \
./vendor/bin/phpunit
```

---

## M — Build de production

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Dans `.env` :

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true    # dès que HTTPS est en place
LOG_LEVEL=warning
```

> Après `config:cache`, les appels à `env()` hors des fichiers `config/`
> retournent `null`. L'application n'en fait aucun : toute valeur
> d'environnement passe par `config/`.

---

## N — Dépannage

### « 500 Internal Server Error » après installation

```bash
php artisan config:clear && php artisan cache:clear
tail -50 storage/logs/laravel.log
sudo chown -R www-data:www-data storage bootstrap/cache
```

Cause la plus fréquente : `APP_KEY` absente ou `storage/` non inscriptible.

### Page blanche, CSS absent

Les assets n'ont pas été compilés, ou l'ont été avant l'écriture des
vues :

```bash
npm run build
php artisan view:clear
```

### « No application encryption key has been specified »

```bash
php artisan key:generate
```

### Les SMS restent « En attente » ou « Dans la file »

Le worker ne tourne pas :

```bash
php artisan queue:work --queue=sms      # test manuel
sudo systemctl status keneya-dme-queue  # service
```

### Les SMS restent « Accepté par la passerelle »

C'est le comportement normal tant que le planificateur n'a pas interrogé
SMSGate :

```bash
php artisan keneya:sms:refresh    # forcer une mise à jour
php artisan schedule:list         # vérifier la planification
```

### SMSGate refuse les identifiants

```bash
php artisan keneya:sms:check
```

Vérifier `SMSGATE_BASE_URL` (le chemin `/3rdparty/v1` est requis), puis
`SMSGATE_USERNAME` / `SMSGATE_PASSWORD`.

### « SQLSTATE[HY000] [2002] Connection refused »

La base n'est pas joignable. En Docker, vérifier que `DB_HOST=db` et non
`127.0.0.1`.

### Erreur 419 (page expirée)

Session expirée ou cookie non transmis. En HTTPS derrière un reverse
proxy, vérifier que `X-Forwarded-Proto` est transmis et que
`APP_URL` correspond bien à l'URL publique.

### Permissions Windows

```powershell
icacls "C:\keneya-dme\storage" /grant "IUSR:(OI)(CI)F" /T
```

### Réinitialisation complète (développement uniquement)

```bash
php artisan migrate:fresh --seed
```

> **Destructif** : supprime toutes les données. Jamais en production.

---

## O — Sécurité

### Liste de contrôle avant mise en service

- [ ] `APP_DEBUG=false` et `APP_ENV=production`
- [ ] `APP_KEY` générée et sauvegardée
- [ ] HTTPS actif, `SESSION_SECURE_COOKIE=true`
- [ ] `DocumentRoot` / `root` pointe sur `public/` **uniquement**
- [ ] `.env` non lisible par le serveur web (vérifier : `curl https://…/.env` → 403/404)
- [ ] `storage/` et `vendor/` inaccessibles par URL
- [ ] `php artisan storage:link` **non exécuté**
- [ ] Mots de passe de base de données et SMSGate robustes et propres à l'installation
- [ ] Sauvegardes de la base **et** de `storage/app/private/` planifiées et testées
- [ ] Comptes de démonstration absents en production (`DEMO_SEED_ENABLED=false`)
- [ ] Journal d'audit consulté périodiquement

### Vérification rapide

```bash
curl -I https://votre-domaine/.env                       # attendu : 403 ou 404
curl -I https://votre-domaine/storage/logs/laravel.log   # attendu : 403 ou 404
curl -I https://votre-domaine/vendor/autoload.php        # attendu : 403 ou 404
curl -I https://votre-domaine/                           # en-têtes de sécurité présents
```

### Données de santé

Cette application traite des données de santé. Selon la réglementation
applicable, leur hébergement peut être soumis à agrément. Le chiffrement
au repos (disque et base) est de la responsabilité de l'exploitant.

---

## P — Mise à jour

### Linux

```bash
cd /var/www/keneya-dme
git pull
./scripts/update.sh
```

### Windows

```powershell
cd C:\keneya-dme
git pull
.\scripts\update.ps1
```

### Docker

```bash
git pull
docker compose build
docker compose up -d
```

Les migrations sont appliquées automatiquement au démarrage du conteneur
`app`.

### Manuellement

```bash
php artisan down                      # maintenance
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

> **Sauvegarder la base et `storage/app/private/` avant toute mise à
> jour appliquant des migrations.**
