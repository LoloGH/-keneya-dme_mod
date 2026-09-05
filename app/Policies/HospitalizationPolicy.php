<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hospitalization;
use App\Models\User;

class HospitalizationPolicy extends DomainPolicy
{
    protected string $viewPermission = 'hospitalizations.view';

    protected string $createPermission = 'hospitalizations.create';

    protected string $updatePermission = 'hospitalizations.update';

    /**
     * La sortie est un acte médical : elle exige le droit d'admission,
     * que l'infirmier ne possède pas (il peut en revanche alimenter le
     * suivi du séjour via `hospitalizations.update`).
     */
    public function discharge(User $user, Hospitalization $hospitalization): bool
    {
        return $this->allows($user, 'hospitalizations.create')
            && $hospitalization->isOngoing();
    }
}
