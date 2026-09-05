<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SmsTemplate;
use App\Support\Rbac;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Paramètres de l'application.
 *
 * Écran de lecture en phase 1 : il expose la configuration effective
 * (établissement, identifiants, SMS, rôles et permissions) afin de rendre
 * l'architecture vérifiable sans accéder au serveur. Les valeurs
 * proviennent de la configuration et des variables d'environnement ;
 * aucun secret n'y est affiché.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'facility' => config('keneya.facility'),
            'identifiers' => config('keneya.identifiers.prefixes'),
            'documents' => config('keneya.documents'),
            'smsGateway' => config('sms.gateway'),
            'smsSimulated' => app(\App\Services\Sms\SmsGatewayManager::class)->isSimulated(),
            'smsTracking' => config('sms.status_tracking'),
            'smsRetry' => config('sms.retry'),
            'services' => Service::orderBy('name')->get(),
            'roles' => Role::withCount('users')->orderBy('name')->get(),
            'roleLabels' => Rbac::roleLabels(),
            'permissionGroups' => Rbac::permissionGroups(),
            'rolePermissions' => Rbac::rolePermissions(),
            'templates' => SmsTemplate::orderBy('name')->get(),
        ]);
    }
}
