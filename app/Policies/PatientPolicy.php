<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Accès au dossier patient.
 *
 * En phase 1 tout professionnel disposant de `patients.view` accède aux
 * dossiers de l'établissement, ce qui correspond au fonctionnement d'un
 * DME hospitalier. La restriction fine (patient de son seul service, ou
 * relation de soin établie) est un point d'évolution documenté dans le
 * README : elle ne peut être tranchée sans règle organisationnelle.
 */
class PatientPolicy extends DomainPolicy
{
    protected string $viewPermission = 'patients.view';

    protected string $createPermission = 'patients.create';

    protected string $updatePermission = 'patients.update';

    protected ?string $deletePermission = 'patients.delete';

    /**
     * Un dossier archivé ou décédé reste consultable, mais n'est plus
     * modifiable en dehors du rôle administrateur.
     */
    public function update(User $user, Model $model): bool
    {
        if (! parent::update($user, $model)) {
            return false;
        }

        if ($model instanceof Patient && $model->status === 'archived') {
            return $user->can('patients.delete');
        }

        return true;
    }
}
