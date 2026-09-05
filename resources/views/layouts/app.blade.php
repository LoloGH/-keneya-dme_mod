<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Un dossier médical ne doit pas être mis en cache par un proxy. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dossier médical') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('assets/logo_kdme.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="min-h-full lg:flex" x-data="{ sidebarOpen: false }">

    {{-- Navigation latérale (§9) — tiroir coulissant sous 1024px (§8) --}}
    <div x-show="sidebarOpen" x-cloak
         class="fixed inset-0 z-30 bg-ink-900/50 lg:hidden"
         @click="sidebarOpen = false" aria-hidden="true"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-ink-200 bg-white
                  transition-transform lg:static lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           x-cloak
           aria-label="Navigation principale">
        @include('partials.sidebar')
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('partials.topbar')

        <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8" id="contenu-principal">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="k-no-print border-t border-ink-200 px-4 py-4 text-xs text-ink-500 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>{{ config('keneya.facility.name') }} — Keneya-DME v{{ config('keneya.version') }}</span>
                <span>Données de démonstration fictives. Aucune donnée médicale réelle.</span>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
