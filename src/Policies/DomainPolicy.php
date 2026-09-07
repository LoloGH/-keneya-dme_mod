<?php

declare(strict_types=1);

namespace Keneya\Dme\Policies;

use Keneya\Dme\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Socle commun des policies du DME.
 *
 * Toutes les autorisations sont vérifiées côté serveur (§32). L'interface
 * masque les actions interdites par confort, mais la décision fait
 * toujours autorité ici : une requête forgée est refusée de la même
 * manière qu'un clic dans l'application.
 *
 * Règle transverse : un compte désactivé ne peut rien faire, quel que
 * soit son rôle.
 */
abstract class DomainPolicy
{
    /** Permission requise pour lire. */
    protected string $viewPermission = '';

    /** Permission requise pour créer. */
    protected string $createPermission = '';

    /** Permission requise pour modifier. */
    protected string $updatePermission = '';

    /** Permission requise pour supprimer / archiver ; null = interdit à tous. */
    protected ?string $deletePermission = null;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, $this->viewPermission);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, $this->viewPermission);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, $this->createPermission);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, $this->updatePermission);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->deletePermission !== null
            && $this->allows($user, $this->deletePermission);
    }

    /**
     * Vérification élémentaire : compte actif et permission accordée.
     */
    protected function allows(User $user, string $permission): bool
    {
        if ($permission === '' || ! $user->isActive()) {
            return false;
        }

        return $user->can($permission);
    }
}
