<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('assets/logo-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-clinic-900">
<div class="relative flex min-h-full flex-col overflow-hidden">
    {{-- Photo d'ambiance en arrière-plan de toute la page, voile dégradé pour la lisibilité (§8) --}}
    <img src="{{ asset('assets/login-hero-bg.jpg') }}" alt=""
         class="absolute inset-0 h-full w-full object-cover object-right-bottom" aria-hidden="true">
    <div class="absolute inset-0 bg-linear-to-br from-clinic-900/80 via-clinic-800/60 to-keneya-700/55" aria-hidden="true"></div>

<div class="relative flex flex-1 flex-col lg:flex-row">

    {{-- Volet d'identité : logo, accroche, slogan. Rien d'autre (§8) --}}
    <div class="relative flex shrink-0 flex-col px-6 py-8 text-white sm:px-10 lg:w-[42%] lg:justify-center lg:px-16 lg:py-12">
        <div class="relative flex items-center gap-3 lg:absolute lg:top-12 lg:left-16">
            <img src="{{ asset('assets/logo-icon.png') }}" alt="" class="h-12 w-12 object-contain">
            <span class="text-lg font-semibold tracking-tight">Keneya <span class="text-keneya-300">DME</span></span>
        </div>

        <div class="relative mt-8 max-w-sm lg:mt-0">
            <p class="text-xs font-semibold tracking-[0.2em] text-keneya-300 uppercase">
                Dossier médical électronique
            </p>
            <h1 class="mt-3 text-2xl font-semibold leading-tight lg:text-[2rem]">
                Tous les dossiers médicaux au même endroit.
            </h1>
        </div>
    </div>

    {{-- Formulaire --}}
    <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-12">
        <div class="w-full max-w-[26rem] rounded-2xl border border-ink-200 bg-white p-8 shadow-sm sm:p-10"
             x-data="{
                 showPassword: false,
                 submitting: false,
                 langOpen: false,
                 lang: 'fr',
                 fillRole(email) {
                     $refs.email.value = email;
                     $refs.email.focus();
                     $refs.password.focus();
                 },
             }">

            {{-- Sélecteur de langue — discret --}}
            <div class="relative -mt-1 mb-2 flex justify-end" @click.outside="langOpen = false">
                <button type="button" class="flex items-center gap-1 text-xs font-medium text-ink-400 transition hover:text-ink-600"
                        @click="langOpen = !langOpen" :aria-expanded="langOpen.toString()" aria-haspopup="listbox">
                    <span x-text="lang === 'fr' ? 'Français' : 'English'"></span>
                    <x-icon name="chevron-down" class="h-3 w-3"/>
                </button>
                <ul x-cloak x-show="langOpen" x-transition.origin.top.right
                    class="absolute top-full right-0 z-10 mt-1 w-32 overflow-hidden rounded-lg border border-ink-200 bg-white py-1 text-left shadow-lg"
                    role="listbox" aria-label="Langue de l'interface">
                    <li role="option" :aria-selected="lang === 'fr'">
                        <button type="button" class="flex w-full items-center justify-between px-3 py-1.5 text-xs text-ink-700 hover:bg-ink-50"
                                @click="lang = 'fr'; langOpen = false">
                            Français
                            <x-icon name="check" class="h-3.5 w-3.5 text-keneya-600" x-show="lang === 'fr'"/>
                        </button>
                    </li>
                    <li role="option" :aria-selected="lang === 'en'">
                        <button type="button" class="flex w-full items-center justify-between px-3 py-1.5 text-xs text-ink-400 hover:bg-ink-50"
                                @click="lang = 'en'; langOpen = false" title="Interface en anglais bientôt disponible">
                            English
                            <span class="text-[9px] font-medium tracking-wide text-ink-400 uppercase">Bientôt</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col items-center text-center">
                <img src="{{ asset('assets/logo-icon.png') }}" alt="Keneya DME" class="h-16 w-16 object-contain">
                <h2 class="mt-4 text-xl font-semibold text-ink-900">Accès sécurisé</h2>
                <p class="mt-1 text-sm text-ink-500">Connectez-vous pour accéder à votre espace</p>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-keneya-500 bg-keneya-50 px-4 py-3 text-sm text-keneya-700"
                     role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="mt-7 space-y-4" @submit="submitting = true">
                @csrf

                <div>
                    <label for="email" class="k-label">Adresse e-mail ou nom d'utilisateur</label>
                    <div class="relative">
                        <x-icon name="mail" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400"/>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" x-ref="email"
                               class="k-input pl-10 @error('email') border-red-500 @enderror"
                               required autofocus autocomplete="username"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    </div>
                    @error('email')
                        <p id="email-error" class="k-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="k-label">Mot de passe</label>
                    <div class="relative">
                        <x-icon name="lock" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400"/>
                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" x-ref="password"
                               class="k-input pl-10 pr-11 @error('password') border-red-500 @enderror"
                               required autocomplete="current-password"
                               @error('password') aria-invalid="true" @enderror>
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-600"
                                :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                :aria-pressed="showPassword.toString()">
                            <x-icon name="eye" class="h-4 w-4" x-show="!showPassword"/>
                            <x-icon name="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak/>
                        </button>
                    </div>
                    <x-field-error name="password"/>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 text-xs">
                    <label class="flex items-center gap-2 text-ink-600">
                        <input type="checkbox" name="remember" value="1"
                               class="h-3.5 w-3.5 rounded border-ink-300 text-clinic-600 focus:ring-clinic-500">
                        Se souvenir de moi
                    </label>
                    <button type="button" class="font-medium text-clinic-600 hover:text-clinic-700"
                            @click="$refs.forgotHelp.hidden = !$refs.forgotHelp.hidden"
                            aria-controls="forgot-password-help" :aria-expanded="(!$refs.forgotHelp?.hidden).toString()">
                        Mot de passe oublié ?
                    </button>
                </div>
                <p x-ref="forgotHelp" id="forgot-password-help" hidden class="k-hint rounded-lg bg-ink-50 px-3 py-2" role="note">
                    Contactez un administrateur pour réinitialiser votre mot de passe.
                </p>

                <button type="submit" :disabled="submitting"
                        class="k-btn w-full bg-linear-to-r from-clinic-700 to-keneya-600 text-white transition hover:from-clinic-800 hover:to-keneya-700 focus-visible:outline-clinic-700 disabled:opacity-80">
                    <x-icon name="login" class="h-4 w-4" x-show="!submitting"/>
                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-text="submitting ? 'Connexion en cours…' : 'Se connecter'"></span>
                </button>
            </form>

            <div class="mt-6 flex items-center gap-3 text-[11px] font-medium text-ink-400" role="separator">
                <span class="h-px flex-1 bg-ink-200"></span>
                ou
                <span class="h-px flex-1 bg-ink-200"></span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <button type="button"
                        class="flex items-center justify-center gap-1.5 rounded-lg border border-ink-200 px-2 py-2 text-[11px] font-medium text-ink-500 transition hover:border-clinic-300 hover:bg-clinic-50 hover:text-clinic-700"
                        @click="fillRole('medecin@keneya.test')">
                    <x-icon name="stethoscope" class="h-3.5 w-3.5"/> Médecin
                </button>
                <button type="button"
                        class="flex items-center justify-center gap-1.5 rounded-lg border border-ink-200 px-2 py-2 text-[11px] font-medium text-ink-500 transition hover:border-clinic-300 hover:bg-clinic-50 hover:text-clinic-700"
                        @click="fillRole('infirmier@keneya.test')">
                    <x-icon name="heart" class="h-3.5 w-3.5"/> Infirmier
                </button>
                <button type="button"
                        class="flex items-center justify-center gap-1.5 rounded-lg border border-ink-200 px-2 py-2 text-[11px] font-medium text-ink-500 transition hover:border-clinic-300 hover:bg-clinic-50 hover:text-clinic-700"
                        @click="fillRole('reception@keneya.test')">
                    <x-icon name="clipboard" class="h-3.5 w-3.5"/> Réception
                </button>
                <button type="button"
                        class="flex items-center justify-center gap-1.5 rounded-lg border border-ink-200 px-2 py-2 text-[11px] font-medium text-ink-500 transition hover:border-clinic-300 hover:bg-clinic-50 hover:text-clinic-700"
                        @click="fillRole('admin@keneya.test')">
                    <x-icon name="cog" class="h-3.5 w-3.5"/> Admin
                </button>
            </div>

            <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-[11px] text-ink-400">
                <x-icon name="lock" class="h-3 w-3 shrink-0"/>
                Vos données médicales sont protégées — accès confidentiel et contrôlé
            </p>
        </div>
    </div>
</div>

<p class="relative pb-6 text-center text-[11px] text-white/60">
    © {{ date('Y') }} Keneya DME · v{{ config('keneya.version') }}
</p>
</div>
</body>
</html>
