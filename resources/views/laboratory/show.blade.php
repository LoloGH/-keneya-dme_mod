@extends('layouts.app')

@section('title', 'Demande '.$order->order_number)

@section('content')
    <x-page-header :title="'Demande '.$order->order_number"
                   :subtitle="$order->requested_at->translatedFormat('l d F Y à H:i')"
                   :breadcrumbs="[
                       'Laboratoire' => route('laboratory.index'),
                       $order->patient->fullName() => route('patients.show', $order->patient),
                       $order->order_number => null,
                   ]">
        <x-slot:actions>
            <a href="{{ route('laboratory.pdf', $order) }}" target="_blank" rel="noopener" class="k-btn-secondary">
                <x-icon name="print" class="h-4 w-4"/> Compte rendu PDF
            </a>
            @can('validateResults', $order)
                <form action="{{ route('laboratory.validate', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="k-btn-primary">Valider les résultats</button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <section class="k-card">
                <div class="k-card-header">
                    <h2 class="k-card-title">Résultats</h2>
                    <div class="flex gap-2">
                        <x-status-badge :status="$order->priority" :label="$order->priorityLabel()"/>
                        <x-status-badge :status="$order->status" :label="$order->statusLabel()"/>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="k-table">
                        <caption class="sr-only">Résultats d'analyse</caption>
                        <thead>
                            <tr>
                                <th scope="col">Examen</th>
                                <th scope="col">Paramètre</th>
                                <th scope="col">Résultat</th>
                                <th scope="col">Unité</th>
                                <th scope="col">Valeurs de référence</th>
                                <th scope="col">Interprétation</th>
                                <th scope="col">Validé par</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                @forelse ($item->results as $result)
                                    <tr class="{{ $result->flag === 'critical' ? 'bg-red-50/60' : '' }}">
                                        <td>{{ $item->exam_name }}</td>
                                        <td class="font-medium text-ink-900">{{ $result->parameter }}</td>
                                        <td class="font-semibold tabular-nums">{{ $result->value }}</td>
                                        <td>{{ $result->unit }}</td>
                                        <td class="text-xs text-ink-500">{{ $result->reference_range ?: '—' }}</td>
                                        <td><x-status-badge :status="$result->flag" :label="$result->flagLabel()"/></td>
                                        <td class="text-xs text-ink-500">{{ $result->validator?->displayName() ?? 'En attente' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>{{ $item->exam_name }}</td>
                                        <td colspan="6" class="text-ink-500">Résultat non encore saisi</td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Saisie des résultats par le laboratoire (§23) --}}
            @can('recordResult', $order)
                <section class="k-card">
                    <div class="k-card-header">
                        <h2 class="k-card-title">Saisir les résultats</h2>
                        <p class="text-xs text-ink-500">Un résultat critique alerte immédiatement le prescripteur.</p>
                    </div>
                    <form action="{{ route('laboratory.results.store', $order) }}" method="POST" class="k-card-body space-y-3">
                        @csrf
                        @foreach ($order->items as $index => $item)
                            <div class="rounded-lg border border-ink-200 p-3">
                                <p class="mb-2 text-sm font-medium text-ink-900">{{ $item->exam_name }}</p>
                                <input type="hidden" name="results[{{ $index }}][lab_order_item_id]" value="{{ $item->id }}">
                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                    <div>
                                        <label for="param_{{ $index }}" class="k-label">Paramètre</label>
                                        <input id="param_{{ $index }}" name="results[{{ $index }}][parameter]" type="text"
                                               maxlength="150" class="k-input" value="{{ $item->exam_name }}">
                                    </div>
                                    <div>
                                        <label for="value_{{ $index }}" class="k-label">Résultat</label>
                                        <input id="value_{{ $index }}" name="results[{{ $index }}][value]" type="text"
                                               maxlength="100" class="k-input">
                                    </div>
                                    <div>
                                        <label for="unit_{{ $index }}" class="k-label">Unité</label>
                                        <input id="unit_{{ $index }}" name="results[{{ $index }}][unit]" type="text"
                                               maxlength="50" class="k-input" placeholder="g/L">
                                    </div>
                                    <div>
                                        <label for="range_{{ $index }}" class="k-label">Réf.</label>
                                        <input id="range_{{ $index }}" name="results[{{ $index }}][reference_range]" type="text"
                                               maxlength="100" class="k-input" placeholder="0.70 – 1.10">
                                    </div>
                                    <div>
                                        <label for="flag_{{ $index }}" class="k-label">Interprétation</label>
                                        <select id="flag_{{ $index }}" name="results[{{ $index }}][flag]" class="k-select">
                                            <option value="normal">Normal</option>
                                            <option value="low">Bas</option>
                                            <option value="high">Élevé</option>
                                            <option value="critical">Critique</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="k-btn-primary">Enregistrer les résultats</button>
                    </form>
                </section>
            @endcan
        </div>

        <div class="space-y-4">
            <section class="k-card">
                <div class="k-card-header"><h2 class="k-card-title">Patient</h2></div>
                <div class="k-card-body"><x-patient-header :patient="$order->patient" compact/></div>
            </section>

            <section class="k-card">
                <div class="k-card-header"><h2 class="k-card-title">Demande</h2></div>
                <dl class="k-card-body space-y-2.5 text-sm">
                    <div>
                        <dt class="text-xs text-ink-500">Prescripteur</dt>
                        <dd class="font-medium text-ink-900">{{ $order->doctor?->displayName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-500">Indication</dt>
                        <dd class="text-ink-800">{{ $order->indication ?: 'Non précisée' }}</dd>
                    </div>
                    @if ($order->completed_at)
                        <div>
                            <dt class="text-xs text-ink-500">Résultats disponibles le</dt>
                            <dd class="text-ink-800">{{ $order->completed_at->translatedFormat('d M Y à H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </div>
    </div>
@endsection
