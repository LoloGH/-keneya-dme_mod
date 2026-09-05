@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
    <x-page-header title="Paramètres"
                   subtitle="Configuration effective de l’application. Les valeurs proviennent des fichiers de configuration et des variables d’environnement ; aucun secret n’est affiché."/>

    <div class="grid gap-4 lg:grid-cols-2">

        <section class="k-card">
            <div class="k-card-header"><h2 class="k-card-title">Établissement</h2></div>
            <dl class="k-card-body space-y-2.5 text-sm">
                @foreach ([
                    'Nom' => $facility['name'],
                    'Adresse' => $facility['address'],
                    'Téléphone' => $facility['phone'],
                    'Adresse e-mail' => $facility['email'],
                ] as $label => $value)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-500">{{ $label }}</dt>
                        <dd class="text-right font-medium text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="k-card">
            <div class="k-card-header"><h2 class="k-card-title">Identifiants métier</h2></div>
            <div class="k-card-body">
                <p class="mb-3 text-sm text-ink-600">
                    Format <span class="font-mono">PRÉFIXE-ANNÉE-SÉQUENCE</span>. Ces identifiants sont
                    stables et jamais réattribués : ils serviront de clé de correspondance lors d’une
                    future intégration FHIR ou HL7.
                </p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($identifiers as $key => $prefix)
                        <div class="rounded-lg border border-ink-200 px-3 py-2">
                            <p class="text-xs text-ink-500">{{ str_replace('_', ' ', $key) }}</p>
                            <p class="font-mono text-sm font-medium text-ink-900">
                                {{ $prefix }}-{{ now()->format('Y') }}-000001
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="k-card">
            <div class="k-card-header"><h2 class="k-card-title">Documents</h2></div>
            <dl class="k-card-body space-y-2.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Disque de stockage</dt>
                    <dd class="font-mono font-medium text-ink-900">{{ $documents['disk'] }} (privé)</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Taille maximale</dt>
                    <dd class="font-medium text-ink-900">{{ round($documents['max_size_kb'] / 1024) }} Mo</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Formats acceptés</dt>
                    <dd class="text-right font-medium text-ink-900">{{ implode(', ', $documents['allowed_mimes']) }}</dd>
                </div>
                <p class="border-t border-ink-100 pt-2.5 text-xs text-ink-500">
                    Aucun document n’est accessible par une URL de fichier. Tout téléchargement passe par
                    une route contrôlée, vérifie la permission de l’utilisateur et est inscrit au journal d’audit.
                </p>
            </dl>
        </section>

        <section class="k-card">
            <div class="k-card-header"><h2 class="k-card-title">Service SMS</h2></div>
            <dl class="k-card-body space-y-2.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Passerelle</dt>
                    <dd class="font-mono font-medium text-ink-900">{{ $smsGateway }}</dd>
                </div>
                @if ($smsSimulated)
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Passerelle de simulation : aucun SMS réel n’est émis.
                        Définissez <span class="font-mono">SMS_GATEWAY=smsgate</span> pour un envoi réel.
                    </p>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Suivi d’acheminement</dt>
                    <dd class="font-medium text-ink-900">
                        {{ $smsTracking['enabled'] ? 'Activé' : 'Désactivé' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Tentatives maximales</dt>
                    <dd class="font-medium text-ink-900">{{ $smsRetry['max_attempts'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-500">Délai entre tentatives</dt>
                    <dd class="font-medium text-ink-900">{{ $smsRetry['delay_seconds'] }} s</dd>
                </div>
                <div>
                    <dt class="mb-1 text-ink-500">Modèles actifs</dt>
                    <dd>
                        <ul class="space-y-0.5">
                            @foreach ($templates as $template)
                                <li class="font-mono text-xs text-ink-700">{{ $template->key }}</li>
                            @endforeach
                        </ul>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="k-card lg:col-span-2">
            <div class="k-card-header">
                <h2 class="k-card-title">Rôles et permissions</h2>
                <span class="text-xs text-ink-500">
                    {{ count(\App\Support\Rbac::allPermissions()) }} permissions · {{ $roles->count() }} rôles
                </span>
            </div>
            <div class="k-card-body">
                <p class="mb-3 text-sm text-ink-600">
                    Les permissions sont vérifiées côté serveur par les policies. L’interface masque les
                    actions interdites par confort, mais un accès direct par URL est refusé de la même manière.
                </p>

                <div class="overflow-x-auto">
                    <table class="k-table">
                        <caption class="sr-only">Matrice des rôles et permissions</caption>
                        <thead>
                            <tr>
                                <th scope="col">Permission</th>
                                @foreach ($roleLabels as $key => $label)
                                    <th scope="col" class="text-center">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissionGroups as $group => $permissions)
                                <tr>
                                    <th scope="colgroup" colspan="{{ count($roleLabels) + 1 }}"
                                        class="bg-ink-50 px-4 py-2 text-left text-xs font-semibold text-ink-700">
                                        {{ $group }}
                                    </th>
                                </tr>
                                @foreach ($permissions as $permission => $description)
                                    <tr>
                                        <td>
                                            <span class="font-mono text-xs text-ink-700">{{ $permission }}</span>
                                            <span class="block text-xs text-ink-500">{{ $description }}</span>
                                        </td>
                                        @foreach ($roleLabels as $roleKey => $roleLabel)
                                            <td class="text-center">
                                                @if (in_array($permission, $rolePermissions[$roleKey] ?? [], true))
                                                    <x-icon name="check" class="mx-auto h-4 w-4 text-keneya-600"/>
                                                    <span class="sr-only">{{ $roleLabel }} : autorisé</span>
                                                @else
                                                    <span class="text-ink-300" aria-hidden="true">—</span>
                                                    <span class="sr-only">{{ $roleLabel }} : non autorisé</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="k-card lg:col-span-2">
            <div class="k-card-header"><h2 class="k-card-title">Services de l’établissement</h2></div>
            <div class="k-card-body grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-ink-200 px-3 py-2">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $service->name }}</p>
                            <p class="font-mono text-xs text-ink-500">{{ $service->code }}</p>
                        </div>
                        <x-status-badge :status="$service->is_active ? 'active' : 'cancelled'"
                                        :label="$service->is_active ? 'Actif' : 'Inactif'"/>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
