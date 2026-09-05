@extends('layouts.app')

@section('title', 'Service SMS')

@section('content')
    <x-page-header title="Service SMS"
                   subtitle="Service transversal : il ne dépend d’aucun module médical et pourra être extrait tel quel en phase 2."/>

    {{-- État du service --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <div class="k-card p-4">
            <p class="text-xs font-medium text-ink-500">Passerelle active</p>
            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $gateway }}</p>
            <p class="mt-0.5 text-[11px] text-ink-400">
                @if ($gateway === 'log')
                    Aucun SMS réel n’est émis : les messages sont journalisés.
                @else
                    Configurée via SMS_DRIVER.
                @endif
            </p>
        </div>
        <div class="k-card p-4">
            <p class="text-xs font-medium text-ink-500">Envoyés</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-keneya-600">{{ $stats['sent'] }}</p>
        </div>
        <div class="k-card p-4">
            <p class="text-xs font-medium text-ink-500">Dans la file</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-clinic-600">{{ $stats['queued'] }}</p>
        </div>
        <div class="k-card p-4">
            <p class="text-xs font-medium text-ink-500">En échec</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-red-600">{{ $stats['failed'] }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <section class="k-card">
                <div class="k-card-header">
                    <h2 class="k-card-title">Historique des envois</h2>
                    <form method="GET" class="flex flex-wrap gap-2">
                        <label for="q" class="sr-only">Rechercher</label>
                        <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="k-input"
                               placeholder="Numéro ou référence…">
                        <label for="status" class="sr-only">Statut</label>
                        <select id="status" name="status" class="k-select">
                            <option value="">Tous</option>
                            @foreach (\App\Models\SmsMessage::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="k-btn-secondary k-btn-sm">Filtrer</button>
                    </form>
                </div>

                @if ($messages->isEmpty())
                    <x-empty-state icon="chat" title="Aucun message"
                                   message="Les SMS déclenchés par les rendez-vous, résultats et ordonnances apparaîtront ici."/>
                @else
                    <div class="overflow-x-auto">
                        <table class="k-table">
                            <caption class="sr-only">Historique des SMS</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Destinataire</th>
                                    <th scope="col">Message</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Essais</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($messages as $message)
                                    <tr>
                                        <td class="whitespace-nowrap text-xs">
                                            {{ $message->created_at->translatedFormat('d M Y H:i') }}
                                        </td>
                                        <td class="font-mono text-xs">{{ $message->recipient }}</td>
                                        <td class="max-w-sm">
                                            <span class="block truncate">{{ $message->body }}</span>
                                            @if ($message->error_message)
                                                <span class="block text-xs text-red-600">{{ $message->error_message }}</span>
                                            @endif
                                        </td>
                                        <td class="text-xs">
                                            @if ($message->patient)
                                                <a href="{{ route('patients.show', $message->patient) }}"
                                                   class="text-clinic-700 hover:underline">
                                                    {{ $message->patient->patient_number }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="tabular-nums">{{ $message->attempts }}</td>
                                        <td><x-status-badge :status="$message->status" :label="$message->statusLabel()"/></td>
                                        <td class="text-right">
                                            @can('retry', $message)
                                                <form action="{{ route('sms.retry', $message) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="k-btn-ghost k-btn-sm">Rejouer</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $messages->links() }}</div>
                @endif
            </section>
        </div>

        <div class="space-y-4">
            @can('sms.send')
                <section class="k-card">
                    <div class="k-card-header"><h2 class="k-card-title">Envoi manuel</h2></div>
                    <form action="{{ route('sms.store') }}" method="POST" class="k-card-body space-y-3">
                        @csrf
                        <div>
                            <label for="recipient" class="k-label">Destinataire <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="recipient" name="recipient" type="tel" required maxlength="30" class="k-input"
                                   value="{{ old('recipient') }}" placeholder="+223 70 00 10 01">
                            <x-field-error name="recipient"/>
                        </div>
                        <div>
                            <label for="body" class="k-label">Message <span class="text-red-600" aria-hidden="true">*</span></label>
                            <textarea id="body" name="body" rows="4" required maxlength="480"
                                      class="k-textarea">{{ old('body') }}</textarea>
                            <p class="k-hint">
                                N’inscrivez jamais de résultat clinique dans un SMS : le réseau n’est pas maîtrisé.
                            </p>
                            <x-field-error name="body"/>
                        </div>
                        <button type="submit" class="k-btn-primary w-full">Placer dans la file d’envoi</button>
                    </form>
                </section>
            @endcan

            <section class="k-card">
                <div class="k-card-header"><h2 class="k-card-title">Modèles de message</h2></div>
                <ul class="k-card-body space-y-3">
                    @foreach ($templates as $template)
                        <li>
                            <p class="text-sm font-medium text-ink-900">
                                {{ $template->name }}
                                @unless ($template->is_active)
                                    <span class="k-badge-neutral ml-1">Désactivé</span>
                                @endunless
                            </p>
                            <p class="mt-0.5 rounded bg-ink-50 px-2 py-1.5 text-xs text-ink-600">{{ $template->body }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@endsection
