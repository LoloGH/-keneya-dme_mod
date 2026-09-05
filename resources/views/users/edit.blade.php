@extends('layouts.app')

@section('title', 'Modifier le compte')

@section('content')
    <x-page-header title="Modifier le compte" :subtitle="$user->displayName()"
                   :breadcrumbs="['Utilisateurs' => route('users.index'), $user->displayName() => null]"/>

    <form action="{{ route('users.update', $user) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        @include('users._form')
        <div class="mt-5 flex flex-wrap gap-2">
            <button type="submit" class="k-btn-primary">Enregistrer</button>
            <a href="{{ route('users.index') }}" class="k-btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
