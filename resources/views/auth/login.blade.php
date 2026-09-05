<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('assets/logo_kdme.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white">
<div class="flex min-h-full">

    {{-- Volet d'identité — masqué sous 1024px pour laisser la place au formulaire (§8) --}}
    <div class="relative hidden w-1/2 flex-col justify-between bg-clinic-900 p-10 text-white lg:flex">
        <div class="flex items-center gap-3">
            <img src="{{ asset('assets/logo_kdme.png') }}" alt="" class="h-11 w-11 rounded-lg bg-white/10 object-contain p-1">
            <span class="text-lg font-semibold">Keneya-DME</span>
        </div>

        <div class="max-w-md">
            <p class="text-xs font-semibold tracking-widest text-clinic-300 uppercase">
                Dossier médical électronique
            </p>
            <h1 class="mt-3 text-3xl font-semibold leading-tight">
                Un dossier unique, pour des soins continus et traçables.
            </h1>
            <p class="mt-4 text-sm leading-relaxed text-clinic-100">
                Patients, consultations, prescriptions, laboratoire, imagerie, hospitalisation
                et soins réunis dans un dossier unique — avec la traçabilité qu'exige la
                pratique hospitalière.
            </p>
        </div>

        <p class="text-xs text-clinic-300">
            Accès réservé aux professionnels autorisés · Toute consultation de dossier est journalisée.
        </p>
    </div>

    {{-- Formulaire --}}
    <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ asset('assets/logo_kdme.png') }}" alt="" class="h-10 w-10 rounded-lg object-contain">
                <span class="text-lg font-semibold text-ink-900">Keneya-DME</span>
            </div>

            <h2 class="text-2xl font-semibold tracking-tight text-ink-900">Connexion</h2>
            <p class="mt-1.5 text-sm text-ink-500">
                {{ config('keneya.facility.name') }}
            </p>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-keneya-500 bg-keneya-50 px-4 py-3 text-sm text-keneya-700"
                     role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="mt-7 space-y-5" x-data="{ show: false }">
                @csrf

                <div>
                    <label for="email" class="k-label">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           class="k-input @error('email') border-red-500 @enderror"
                           required autofocus autocomplete="username"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')
                        <p id="email-error" class="k-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="k-label">Mot de passe</label>
                    <div class="flex gap-2">
                        <input id="password" name="password" :type="show ? 'text' : 'password'"
                               class="k-input @error('password') border-red-500 @enderror"
                               required autocomplete="current-password">
                        <button type="button" @click="show = !show" class="k-btn-secondary shrink-0"
                                :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                            <span x-text="show ? 'Masquer' : 'Afficher'"></span>
                        </button>
                    </div>
                    <x-field-error name="password"/>
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-600">
                    <input type="checkbox" name="remember" value="1"
                           class="h-4 w-4 rounded border-ink-300 text-clinic-600 focus:ring-clinic-500">
                    Rester connecté sur cet appareil
                </label>

                <button type="submit" class="k-btn-primary w-full">Se connecter</button>
            </form>

            <p class="mt-8 text-xs leading-relaxed text-ink-400">
                Cette application contient exclusivement des données fictives de démonstration.
                Les identifiants de démonstration sont créés par le seeder local et documentés
                dans le README.
            </p>
        </div>
    </div>
</div>
</body>
</html>
