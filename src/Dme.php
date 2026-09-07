<?php

declare(strict_types=1);

namespace Keneya\Dme;

use Closure;

/**
 * Point d'entrée du module pour l'application hôte.
 *
 * Tout ce qu'un hôte (Keneya Workflow, ou une application de test) a
 * besoin de déclarer au module passe par ici. La classe ne contient
 * volontairement aucune logique métier : ce sont des points d'accroche.
 */
final class Dme
{
    /**
     * Résolveur d'autorisation d'accès fourni par l'hôte.
     *
     * @var (Closure(mixed, mixed): bool)|null
     */
    private static ?Closure $accessResolver = null;

    /**
     * Déclare comment l'hôte accorde l'accès au module.
     *
     * À appeler depuis un fournisseur de services de l'application hôte :
     *
     *     Dme::authorizeAccessUsing(
     *         fn ($user) => $user->hasPermissionTo('dossier-medical.acceder')
     *     );
     *
     * Passer `null` retire le résolveur : le module retombe alors sur la
     * capacité et l'attribut décrits dans `config/dme.php`.
     *
     * @param  (Closure(mixed, mixed): bool)|null  $callback
     */
    public static function authorizeAccessUsing(?Closure $callback): void
    {
        self::$accessResolver = $callback;
    }

    /**
     * @return (Closure(mixed, mixed): bool)|null
     */
    public static function accessResolver(): ?Closure
    {
        return self::$accessResolver;
    }

    /**
     * Remet le module dans son état initial. Réservé aux tests.
     */
    public static function flushState(): void
    {
        self::$accessResolver = null;
    }

    /**
     * Version fonctionnelle du module.
     */
    public static function version(): string
    {
        return (string) config('dme.version', '0.0.0');
    }

    /**
     * URL d'une ressource statique du module.
     *
     * Les fichiers du module sont copiés dans le répertoire public de
     * l'hôte par `php artisan vendor:publish --tag=dme-assets`. La version
     * du module est ajoutée en paramètre pour qu'une mise à jour ne serve
     * jamais un fichier mis en cache par le navigateur.
     */
    public static function asset(string $path): string
    {
        $base = trim((string) config('dme.assets.path', 'vendor/dme'), '/');

        return asset($base.'/'.ltrim($path, '/')).'?v='.self::version();
    }
}
