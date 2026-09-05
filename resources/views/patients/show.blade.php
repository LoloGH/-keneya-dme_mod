@extends('layouts.app')

@section('title', $patient->fullName())

@php
    /** Onglets du dossier médical électronique (§14). */
    $tabs = [
        'resume' => 'Résumé',
        'consultations' => 'Consultations',
        'antecedents' => 'Antécédents',
        'allergies' => 'Allergies',
        'medicaments' => 'Médicaments',
        'ordonnances' => 'Ordonnances',
        'laboratoire' => 'Laboratoire',
        'imagerie' => 'Imagerie',
        'hospitalisations' => 'Hospitalisations',
        'soins' => 'Soins',
        'rendez-vous' => 'Rendez-vous',
        'documents' => 'Documents',
        'historique' => 'Historique',
        'audit' => 'Audit',
    ];
@endphp

@section('content')
    <nav class="mb-3 flex items-center gap-1 text-xs text-ink-500" aria-label="Fil d'Ariane">
        <a href="{{ route('patients.index') }}" class="flex items-center gap-1 hover:text-clinic-700 hover:underline">
            <x-icon name="arrow-left" class="h-3.5 w-3.5"/> Patients
        </a>
        <x-icon name="chevron-right" class="h-3.5 w-3.5"/>
        <span class="font-medium text-ink-700" aria-current="page">{{ $patient->fullName() }}</span>
    </nav>

    {{-- En-tête patient permanent (§13) --}}
    <section class="k-card mb-4">
        <div class="p-4 sm:p-5">
            <x-patient-header :patient="$patient">
                <x-slot:actions>
                    @can('update', $patient)
                        <a href="{{ route('patients.edit', $patient) }}" class="k-btn-secondary k-btn-sm">Modifier</a>
                    @endcan
                    <a href="{{ route('patients.summary-pdf', $patient) }}" target="_blank" rel="noopener"
                       class="k-btn-secondary k-btn-sm">
                        <x-icon name="print" class="h-4 w-4"/> Fiche PDF
                    </a>
                </x-slot:actions>
            </x-patient-header>

            {{-- Alertes cliniques permanentes (§13) --}}
            @if ($patient->criticalAllergies()->isNotEmpty() || $patient->activeConditions()->isNotEmpty())
                <div class="mt-4">
                    <x-medical-alerts :patient="$patient"/>
                </div>
            @endif

            {{-- Actions rapides du DME (§51) --}}
            <div class="mt-4 flex flex-wrap gap-2 border-t border-ink-100 pt-4">
                @can('consultations.create')
                    <a href="{{ route('consultations.create', $patient) }}" class="k-btn-primary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Consultation
                    </a>
                @endcan
                @can('prescriptions.create')
                    <a href="{{ route('prescriptions.create', $patient) }}" class="k-btn-secondary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Ordonnance
                    </a>
                @endcan
                @can('laboratory.orders.create')
                    <a href="{{ route('laboratory.create', $patient) }}" class="k-btn-secondary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Examen biologique
                    </a>
                @endcan
                @can('imaging.create')
                    <a href="{{ route('imaging.create', $patient) }}" class="k-btn-secondary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Imagerie
                    </a>
                @endcan
                @can('hospitalizations.create')
                    <a href="{{ route('hospitalizations.create', $patient) }}" class="k-btn-secondary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Admission
                    </a>
                @endcan
                @can('appointments.manage')
                    <a href="{{ route('patients.show', [$patient, 'tab' => 'rendez-vous']) }}" class="k-btn-secondary k-btn-sm">
                        <x-icon name="plus" class="h-3.5 w-3.5"/> Rendez-vous
                    </a>
                @endcan
            </div>
        </div>

        {{-- Onglets défilables sur mobile (§8) --}}
        <div class="px-4 sm:px-5">
            <nav class="k-tabs" aria-label="Sections du dossier médical">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('patients.show', [$patient, 'tab' => $key]) }}"
                       class="{{ $tab === $key ? 'k-tab-active' : 'k-tab' }}"
                       @if ($tab === $key) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    @include('patients.tabs.'.(view()->exists('patients.tabs.'.$tab) ? $tab : 'resume'))
@endsection
