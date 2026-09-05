@extends('layouts.app')

@section('title', 'Nouvelle consultation')

@section('content')
    <x-page-header title="Nouvelle consultation"
                   :subtitle="$patient->fullName().' — '.$patient->patient_number"
                   :breadcrumbs="[
                       'Patients' => route('patients.index'),
                       $patient->fullName() => route('patients.show', $patient),
                       'Nouvelle consultation' => null,
                   ]"/>

    <form action="{{ route('consultations.store', $patient) }}" method="POST" novalidate>
        @csrf
        @include('consultations._form', ['consultation' => null])

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <button type="submit" name="action" value="save" class="k-btn-primary">Enregistrer</button>
            @can('prescriptions.create')
                <button type="submit" name="action" value="prescribe" class="k-btn-secondary">
                    Enregistrer et prescrire
                </button>
            @endcan
            <a href="{{ route('patients.show', $patient) }}" class="k-btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
