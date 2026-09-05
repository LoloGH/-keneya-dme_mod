@extends('layouts.app')

@section('title', 'Modifier la consultation')

@section('content')
    <x-page-header title="Modifier la consultation"
                   :subtitle="$consultation->consultation_number.' — '.$patient->fullName()"
                   :breadcrumbs="[
                       'Consultations' => route('consultations.index'),
                       $consultation->consultation_number => route('consultations.show', $consultation),
                       'Modifier' => null,
                   ]"/>

    <form action="{{ route('consultations.update', $consultation) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        @include('consultations._form')

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <button type="submit" class="k-btn-primary">Enregistrer les modifications</button>
            <a href="{{ route('consultations.show', $consultation) }}" class="k-btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
