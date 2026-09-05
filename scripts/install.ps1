<#
.SYNOPSIS
    Installation de Keneya-DME sur Windows.

.DESCRIPTION
    Équivalent PowerShell de scripts/install.sh. Le script est idempotent :
    il peut être relancé sans risque.

.PARAMETER WithDemo
    Charge également les données de démonstration. Nécessite que
    DEMO_USER_PASSWORD soit défini dans le fichier .env.

.EXAMPLE
    .\scripts\install.ps1

.EXAMPLE
    .\scripts\install.ps1 -WithDemo

.NOTES
    Si l'exécution de scripts est bloquée :
        Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#>

[CmdletBinding()]
param(
    [switch]$WithDemo
)

$ErrorActionPreference = 'Stop'

function Write-Info { param([string]$Message) Write-Host "> $Message" -ForegroundColor Cyan }
function Write-Ok   { param([string]$Message) Write-Host "OK  $Message" -ForegroundColor Green }
function Write-Fail {
    param([string]$Message)
    Write-Host "ERREUR  $Message" -ForegroundColor Red
    exit 1
}

# ---------------------------------------------------------------------
# Prérequis
# ---------------------------------------------------------------------
Write-Info 'Verification des prerequis...'

foreach ($tool in @('php', 'composer', 'npm')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        Write-Fail "$tool est introuvable dans le PATH."
    }
}

$phpVersion = (& php -r 'echo PHP_VERSION;')
& php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'
if ($LASTEXITCODE -ne 0) {
    Write-Fail "PHP $phpVersion est trop ancien : 8.2 minimum."
}

$modules = (& php -m)
foreach ($ext in @('mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'curl')) {
    if ($modules -notcontains $ext) {
        Write-Fail "Extension PHP manquante : $ext. Activez extension=$ext dans php.ini."
    }
}

Write-Ok "PHP $phpVersion et dependances presentes."

# ---------------------------------------------------------------------
# Dépendances
# ---------------------------------------------------------------------
Write-Info 'Installation des dependances PHP...'
& composer install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) { Write-Fail 'composer install a echoue.' }

Write-Info 'Installation des dependances JavaScript...'
& npm install
if ($LASTEXITCODE -ne 0) { Write-Fail 'npm install a echoue.' }

# ---------------------------------------------------------------------
# Environnement
# ---------------------------------------------------------------------
if (-not (Test-Path '.env')) {
    Write-Info 'Creation du fichier .env...'
    Copy-Item '.env.example' '.env'
    Write-Ok '.env cree a partir de .env.example.'
} else {
    Write-Ok '.env deja present : conserve en l etat.'
}

if (-not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:' -Quiet)) {
    Write-Info 'Generation de la cle applicative...'
    & php artisan key:generate
}

# ---------------------------------------------------------------------
# Base de données
# ---------------------------------------------------------------------
$connection = 'sqlite'
$match = Select-String -Path '.env' -Pattern '^DB_CONNECTION=(.*)$'
if ($match) { $connection = $match.Matches[0].Groups[1].Value.Trim('"') }

if ($connection -eq 'sqlite') {
    $dbFile = Join-Path 'database' 'database.sqlite'
    if (-not (Test-Path $dbFile)) {
        Write-Info 'Creation de la base SQLite...'
        New-Item -ItemType File -Path $dbFile -Force | Out-Null
    }
}

Write-Info 'Application des migrations...'
if ($WithDemo) {
    if (-not (Select-String -Path '.env' -Pattern '^DEMO_USER_PASSWORD=.+' -Quiet)) {
        Write-Fail 'DEMO_USER_PASSWORD doit etre defini dans .env avant de charger les donnees de demonstration.'
    }
    & php artisan migrate --seed --force
} else {
    & php artisan migrate --force
}
if ($LASTEXITCODE -ne 0) { Write-Fail 'Les migrations ont echoue.' }

# ---------------------------------------------------------------------
# Assets et répertoires
# ---------------------------------------------------------------------
Write-Info 'Compilation des assets...'
& npm run build
if ($LASTEXITCODE -ne 0) { Write-Fail 'La compilation des assets a echoue.' }

Write-Info 'Preparation des repertoires inscriptibles...'
$directories = @(
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'storage\app\private\medical-documents',
    'bootstrap\cache'
)
foreach ($directory in $directories) {
    New-Item -ItemType Directory -Path $directory -Force | Out-Null
}

Write-Ok 'Installation terminee.'
Write-Host ''
Write-Host '  Demarrer le serveur de developpement :'
Write-Host '    php artisan serve'
Write-Host ''
Write-Host '  Traiter la file d envoi des SMS :'
Write-Host '    php artisan queue:work --queue=sms'
Write-Host ''
Write-Host '  Verifier la passerelle SMS :'
Write-Host '    php artisan keneya:sms:check'
Write-Host ''
if ($WithDemo) { Write-Host '  Comptes de demonstration : voir README.md' }
