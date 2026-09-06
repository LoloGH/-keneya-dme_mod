<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\SmsTemplate;
use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Paramètres.
 *
 * L'écran est double, et la distinction est une règle de sécurité, pas
 * une commodité d'affichage :
 *
 *  - sans `settings.manage`, l'utilisateur ne voit que son propre compte
 *    et n'y change que son mot de passe. La configuration de
 *    l'établissement, les passerelles SMS et la matrice de permissions ne
 *    le concernent pas et ne lui sont pas rendues ;
 *  - avec, il obtient la configuration effective et la matrice éditable.
 *
 * Le changement de mot de passe, lui, est ouvert à tous : c'est son
 * propre compte, aucune permission n'a à le conditionner.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user->can('settings.manage')) {
            return view('settings.account', [
                'user' => $user->load(['service', 'roles']),
                'roleLabels' => Rbac::roleLabels(),
            ]);
        }

        return view('settings.index', [
            'user' => $user->load(['service', 'roles']),
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
            // L'état réel, tel qu'il est en base : depuis que la matrice est
            // modifiable, le tableau d'amorçage de Rbac ne la décrit plus.
            'rolePermissions' => Rbac::persistedRolePermissions(),
            'lockedPermissions' => Rbac::lockedAdminPermissions(),
            'canEditRoles' => $user->can('roles.manage'),
            'templates' => SmsTemplate::orderBy('name')->get(),
        ]);
    }

    /**
     * Changement de mot de passe par l'intéressé.
     *
     * L'ancien mot de passe est exigé : sans lui, une session laissée
     * ouverte suffirait à confisquer le compte. Les autres sessions sont
     * invalidées, et le mot de passe n'apparaît dans aucune trace.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Le nouveau mot de passe doit différer de l’ancien.',
        ], [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ]);

        $user->forceFill(['password' => Hash::make($request->string('password')->toString())])->save();

        // La session courante reste valide, les autres tombent : un mot de
        // passe changé doit couper un accès qu'on soupçonne compromis.
        $request->session()->regenerate();

        AuditLog::record(
            action: 'password_changed',
            subject: $user,
            description: 'A changé son mot de passe',
        );

        return back()->with('success', 'Mot de passe mis à jour.');
    }

    /**
     * Prise et fin de garde, déclarées par l'intéressé.
     *
     * C'est ce drapeau qui décide de la visibilité des soins programmés
     * laissés ouverts : il est donc journalisé comme un acte, pas comme
     * une préférence d'affichage.
     */
    public function toggleDuty(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->can('care_orders.view'), 403);

        $onDuty = ! $user->is_on_duty;

        $user->forceFill([
            'is_on_duty' => $onDuty,
            'on_duty_since' => $onDuty ? now() : null,
        ])->save();

        AuditLog::record(
            action: $onDuty ? 'duty_started' : 'duty_ended',
            subject: $user,
            description: $onDuty ? 'A pris la garde' : 'A quitté la garde',
        );

        return back()->with('success', $onDuty
            ? 'Vous êtes de garde. Les soins ouverts de votre service vous sont visibles.'
            : 'Vous n’êtes plus de garde.');
    }
}
