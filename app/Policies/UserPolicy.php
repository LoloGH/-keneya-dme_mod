<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/** Administration des comptes (§31). */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user) || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Un compte n'est jamais supprimé (il porte l'historique médical) :
     * il est désactivé. On empêche aussi un administrateur de se
     * désactiver lui-même, ce qui pourrait fermer l'accès à l'application.
     */
    public function deactivate(User $user, User $target): bool
    {
        return $this->viewAny($user) && ! $user->is($target);
    }
}
