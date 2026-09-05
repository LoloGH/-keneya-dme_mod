@extends('layouts.app')

@section('title', 'Modifier le dossier')

@section('content')
    <x-page-header title="Modifier le dossier"
                   :subtitle="$patient->fullName().' — '.$patient->patient_number"
                   :breadcrumbs="[
                       'Patients' => route('patients.index'),
                       $patient->fullName() => route('patients.show', $patient),
                       'Modifier' => null,
                   ]"/>

    <form action="{{ route('patients.update', $patient) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        @include('patients._form')

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <button type="submit" class="k-btn-primary">Enregistrer les modifications</button>
            <a href="{{ route('patients.show', $patient) }}" class="k-btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
