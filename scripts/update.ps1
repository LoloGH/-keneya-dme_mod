<#
.SYNOPSIS
    Mise a jour d'une installation Keneya-DME existante sur Windows.

.DESCRIPTION
    Equivalent PowerShell de scripts/update.sh. L'application est mise en
    maintenance pendant l'operation, puis remise en ligne — y compris en
    cas d'echec.

.EXAMPLE
    .\scripts\update.ps1
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

function Write-Info { param([string]$Message) Write-Host "> $Message" -ForegroundColor Cyan }

try {
    Write-Info 'Passage en maintenance...'
    & php artisan down --render="errors::503"

    Write-Info 'Dependances PHP...'
    & composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

    Write-Info 'Dependances JavaScript et assets...'
    & npm ci
    & npm run build

    Write-Info 'Migrations...'
    & php artisan migrate --force

    Write-Info 'Reconstruction des caches...'
    & php artisan config:cache
    & php artisan route:cache
    & php artisan view:cache

    Write-Info 'Redemarrage des workers...'
    & php artisan queue:restart

    Write-Host 'OK  Mise a jour terminee.' -ForegroundColor Green
}
finally {
    # L'application est remise en ligne meme si une etape a echoue.
    & php artisan up
}
