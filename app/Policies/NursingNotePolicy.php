<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Soins infirmiers (§26) — transmissions non réinscriptibles. */
class NursingNotePolicy extends DomainPolicy
{
    protected string $viewPermission = 'nursing.view';

    protected string $createPermission = 'nursing.create';

    protected string $updatePermission = '';

    public function update(User $user, Model $model): bool
    {
        return false;
    }
}
