@php $user = $user ?? null; @endphp

<div class="grid gap-4 lg:grid-cols-2">
    <fieldset class="k-fieldset">
        <legend class="k-fieldset-legend">
            <x-icon name="users" class="h-4.5 w-4.5 text-clinic-600"/> Identité professionnelle
        </legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="k-label">Prénom <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="first_name" name="first_name" type="text" required maxlength="100"
                       value="{{ old('first_name', $user?->first_name) }}" class="k-input">
                <x-field-error name="first_name"/>
            </div>
            <div>
                <label for="last_name" class="k-label">Nom <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="last_name" name="last_name" type="text" required maxlength="100"
                       value="{{ old('last_name', $user?->last_name) }}" class="k-input">
                <x-field-error name="last_name"/>
            </div>
            <div>
                <label for="title" class="k-label">Titre</label>
                <input id="title" name="title" type="text" maxlength="20"
                       value="{{ old('title', $user?->title) }}" class="k-input" placeholder="Dr, Pr, M., Mme">
            </div>
            <div>
                <label for="matricule" class="k-label">Matricule</label>
                <input id="matricule" name="matricule" type="text" maxlength="50"
                       value="{{ old('matricule', $user?->matricule) }}" class="k-input">
                <x-field-error name="matricule"/>
            </div>
            <div class="sm:col-span-2">
                <label for="speciality" class="k-label">Spécialité / fonction</label>
                <input id="speciality" name="speciality" type="text" maxlength="100"
                       value="{{ old('speciality', $user?->speciality) }}" class="k-input">
            </div>
        </div>
    </fieldset>

    <fieldset class="k-fieldset">
        <legend class="k-fieldset-legend">
            <x-icon name="chat" class="h-4.5 w-4.5 text-clinic-600"/> Contact et affectation
        </legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="email" class="k-label">Adresse e-mail <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" required maxlength="150"
                       value="{{ old('email', $user?->email) }}" class="k-input" autocomplete="username">
                <x-field-error name="email"/>
            </div>
            <div>
                <label for="phone" class="k-label">Téléphone</label>
                <input id="phone" name="phone" type="tel" maxlength="30"
                       value="{{ old('phone', $user?->phone) }}" class="k-input">
            </div>
            <div>
                <label for="service_id" class="k-label">Service</label>
                <select id="service_id" name="service_id" class="k-select">
                    <option value="">Non affecté</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}"
                            @selected((string) old('service_id', $user?->service_id) === (string) $service->id)>
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset class="k-fieldset">
        <legend class="k-fieldset-legend">
            <x-icon name="shield" class="h-4.5 w-4.5 text-clinic-600"/> Rôle et accès
        </legend>
        <div>
            <label for="role" class="k-label">Rôle <span class="text-red-600" aria-hidden="true">*</span></label>
            <select id="role" name="role" required class="k-select">
                @foreach ($roleLabels as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('role', $user?->roles->first()?->name) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-field-error name="role"/>
            <p class="k-hint">
                Le rôle détermine les permissions. Le détail de chaque rôle est consultable
                dans <a href="{{ route('settings.index') }}" class="text-clinic-700 underline">Paramètres</a>.
            </p>
        </div>

        @if ($user)
            <label class="flex items-start gap-2 text-sm text-ink-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))
                       class="mt-0.5 h-4 w-4 rounded border-ink-300 text-clinic-600">
                <span>
                    Compte actif
                    <span class="block text-xs text-ink-500">
                        Un compte désactivé ne peut plus se connecter, mais conserve l’intégralité de son
                        historique et de ses signatures d’actes. Les comptes ne sont jamais supprimés.
                    </span>
                </span>
            </label>
            <x-field-error name="is_active"/>
        @endif
    </fieldset>

    <fieldset class="k-fieldset">
        <legend class="k-fieldset-legend">Mot de passe</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="k-label">
                    Mot de passe
                    @unless ($user) <span class="text-red-600" aria-hidden="true">*</span> @endunless
                </label>
                <input id="password" name="password" type="password" @required(! $user) class="k-input"
                       autocomplete="new-password">
                <x-field-error name="password"/>
            </div>
            <div>
                <label for="password_confirmation" class="k-label">Confirmation</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="k-input"
                       autocomplete="new-password">
            </div>
        </div>
        <p class="k-hint">
            Minimum 12 caractères, avec majuscules, minuscules et chiffres.
            @if ($user) Laissez vide pour conserver le mot de passe actuel. @endif
        </p>
    </fieldset>
</div>
