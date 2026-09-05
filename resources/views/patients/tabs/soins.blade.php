{{-- Soins infirmiers (§26) — timeline chronologique --}}
<div class="grid gap-4 lg:grid-cols-3">
    <section class="k-card lg:col-span-2">
        <div class="k-card-header">
            <h2 class="k-card-title">Soins et transmissions</h2>
            <span class="text-xs text-ink-500">{{ $tabData['nursingNotes']->total() }} entrée(s)</span>
        </div>

        @if ($tabData['nursingNotes']->isEmpty())
            <x-empty-state icon="heart" title="Aucun soin enregistré"
                           message="Constantes, soins, administrations et transmissions se consignent ici, horodatés et signés."/>
        @else
            <div class="k-card-body">
                <ol class="relative space-y-3 border-l border-ink-200 pl-5">
                    @foreach ($tabData['nursingNotes'] as $note)
                        <li class="relative">
                            <span class="absolute top-2 -left-[27px] flex h-3 w-3 rounded-full border-2 border-white
                                @class([
                                    'bg-red-500' => $note->severity === 'critical',
                                    'bg-amber-500' => $note->severity === 'warning',
                                    'bg-clinic-500' => $note->severity === 'info',
                                ])"></span>
                            <div class="rounded-lg border border-ink-200 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-ink-500">
                                            {{ $note->occurred_at->translatedFormat('d M Y · H:i') }}
                                        </span>
                                        <span class="k-badge-info">{{ $note->typeLabel() }}</span>
                                    </div>
                                    @if ($note->severity !== 'info')
                                        <x-status-badge :status="$note->severity"
                                            :label="$note->severity === 'critical' ? 'Critique' : 'Vigilance'"/>
                                    @endif
                                </div>
                                <p class="mt-1.5 text-sm font-medium text-ink-900">{{ $note->title }}</p>
                                @if ($note->content)
                                    <p class="mt-0.5 text-sm text-ink-600">{{ $note->content }}</p>
                                @endif
                                @if ($note->medication_name)
                                    <p class="mt-1 text-xs text-ink-500">
                                        {{ $note->medication_name }}
                                        @if ($note->medication_dose) · {{ $note->medication_dose }} @endif
                                        @if ($note->medication_route) · {{ $note->medication_route }} @endif
                                    </p>
                                @endif
                                <p class="mt-1.5 text-xs text-ink-400">{{ $note->nurse?->displayName() ?? 'Auteur non renseigné' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-4">{{ $tabData['nursingNotes']->links() }}</div>
            </div>
        @endif
    </section>

    @can('nursing.create')
        <section class="k-card self-start">
            <div class="k-card-header"><h2 class="k-card-title">Enregistrer un soin</h2></div>
            <form action="{{ route('nursing.store', $patient) }}" method="POST" class="k-card-body space-y-3"
                  x-data="{ type: 'care' }">
                @csrf
                <div>
                    <label for="nursing_type" class="k-label">Type <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="nursing_type" name="type" x-model="type" required class="k-select">
                        @foreach (\App\Models\NursingNote::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="occurred_at" class="k-label">Date et heure <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="occurred_at" name="occurred_at" type="datetime-local" required
                           value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="k-input">
                </div>
                <div>
                    <label for="nursing_title" class="k-label">Intitulé <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="nursing_title" name="title" type="text" required maxlength="200" class="k-input">
                </div>

                <div x-show="type === 'medication_administration'" x-cloak class="space-y-3">
                    <div>
                        <label for="medication_name" class="k-label">Médicament</label>
                        <input id="medication_name" name="medication_name" type="text" maxlength="200" class="k-input">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="medication_dose" class="k-label">Dose</label>
                            <input id="medication_dose" name="medication_dose" type="text" maxlength="100" class="k-input">
                        </div>
                        <div>
                            <label for="medication_route" class="k-label">Voie</label>
                            <input id="medication_route" name="medication_route" type="text" maxlength="50" class="k-input">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="nursing_content" class="k-label">Observation</label>
                    <textarea id="nursing_content" name="content" rows="3" maxlength="5000" class="k-textarea"></textarea>
                </div>
                <div>
                    <label for="severity" class="k-label">Criticité <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="severity" name="severity" required class="k-select">
                        <option value="info">Information</option>
                        <option value="warning">Vigilance</option>
                        <option value="critical">Critique</option>
                    </select>
                </div>
                <button type="submit" class="k-btn-primary w-full">Enregistrer le soin</button>
            </form>
        </section>
    @endcan
</div>
