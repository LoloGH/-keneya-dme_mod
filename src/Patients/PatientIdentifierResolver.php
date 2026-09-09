<?php

declare(strict_types=1);

namespace Keneya\Dme\Patients;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Keneya\Dme\Models\Patient;
use Keneya\Dme\Models\PatientIdentifier;
use InvalidArgumentException;

/**
 * Liaison entre un patient de l'application hôte et le patient du DME.
 *
 * Le module reçoit un patient déjà identifié par l'hôte : un couple
 * (système, valeur), par exemple `keneya_workflow` / `PAT-2026-000123`,
 * accompagné d'un jeu d'informations de base. À partir de là :
 *
 *   - si ce couple est déjà connu, le patient DME correspondant est
 *     retourné tel quel, sans jamais écraser son dossier ;
 *   - sinon, un patient DME est créé à partir des informations reçues,
 *     puis l'identifiant externe lui est rattaché.
 *
 * L'unicité est garantie par la contrainte `unique(system, value)` de la
 * table `patient_identifiers` : deux appels successifs avec les mêmes
 * valeurs renvoient le même patient, sans doublon.
 *
 * Cette classe ne dépend d'aucun hôte réel : c'est elle qui sera appelée
 * par le point d'entrée « Mes patients » de Keneya Workflow en phase B,
 * mais elle est complète et vérifiable dès maintenant.
 */
class PatientIdentifierResolver
{
    /**
     * Résout, ou crée, le patient DME correspondant à un identifiant
     * externe.
     *
     * @param  string  $system  Système d'identification, par ex. « keneya_workflow ».
     * @param  string  $value  Valeur de l'identifiant dans ce système.
     * @param  array{
     *     last_name?: string|null,
     *     first_name?: string|null,
     *     name?: string|null,
     *     sex?: string|null,
     *     birth_date?: string|\DateTimeInterface|null,
     *     age?: int|string|null,
     *     phone?: string|null,
     *     email?: string|null,
     *     address?: string|null,
     *     city?: string|null,
     *     label?: string|null
     * }  $attributes Informations de base transmises par l'hôte.
     */
    public function resolve(string $system, string $value, array $attributes = []): Patient
    {
        $system = trim($system);
        $value = trim($value);

        if ($system === '' || $value === '') {
            throw new InvalidArgumentException(
                'Un identifiant externe exige un système et une valeur non vides.'
            );
        }

        $existing = $this->find($system, $value);

        if ($existing !== null) {
            return $existing;
        }

        // La transaction couvre création du patient et rattachement de
        // l'identifiant : un patient ne doit jamais rester orphelin de
        // l'identifiant qui a justifié sa création.
        return DB::transaction(function () use ($system, $value, $attributes): Patient {
            // Deuxième lecture à l'intérieur de la transaction : deux
            // appels concurrents pour le même identifiant ne doivent
            // produire qu'un seul patient.
            $existing = $this->find($system, $value);

            if ($existing !== null) {
                return $existing;
            }

            $patient = Patient::create($this->patientAttributes($attributes));

            PatientIdentifier::create([
                'patient_id' => $patient->getKey(),
                'system' => $system,
                'value' => $value,
                'label' => $attributes['label'] ?? null,
                'is_primary' => ! $patient->identifiers()->where('is_primary', true)->exists(),
            ]);

            return $patient->fresh();
        });
    }

    /**
     * Retrouve le patient DME d'un identifiant externe, sans rien créer.
     */
    public function find(string $system, string $value): ?Patient
    {
        return PatientIdentifier::query()
            ->where('system', trim($system))
            ->where('value', trim($value))
            ->with('patient')
            ->first()?->patient;
    }

    /**
     * Comme find(), mais échoue plutôt que de retourner null.
     */
    public function findOrFail(string $system, string $value): Patient
    {
        $patient = $this->find($system, $value);

        if ($patient === null) {
            throw new ModelNotFoundException(
                "Aucun patient du DME ne correspond à l'identifiant {$system}/{$value}."
            );
        }

        return $patient;
    }

    /**
     * Rattache un identifiant externe à un patient DME déjà existant.
     *
     * Réappliqué avec les mêmes valeurs, il ne crée pas de doublon ; il
     * refuse en revanche de déplacer un identifiant déjà attribué à un
     * autre patient, qui serait une fusion de dossiers déguisée.
     */
    public function link(Patient $patient, string $system, string $value, ?string $label = null): PatientIdentifier
    {
        $system = trim($system);
        $value = trim($value);

        $identifier = PatientIdentifier::query()
            ->where('system', $system)
            ->where('value', $value)
            ->first();

        if ($identifier !== null && (int) $identifier->patient_id !== (int) $patient->getKey()) {
            throw new InvalidArgumentException(
                "L'identifiant {$system}/{$value} est déjà rattaché à un autre patient."
            );
        }

        if ($identifier !== null) {
            return $identifier;
        }

        return PatientIdentifier::create([
            'patient_id' => $patient->getKey(),
            'system' => $system,
            'value' => $value,
            'label' => $label,
            'is_primary' => ! $patient->identifiers()->where('is_primary', true)->exists(),
        ]);
    }

    /**
     * Traduit les informations de base reçues de l'hôte en colonnes du
     * patient DME.
     *
     * Le nom est le seul élément réellement exigé : un dossier sans nom
     * n'a pas de sens. Tout le reste est facultatif, l'hôte n'ayant pas
     * nécessairement la même richesse d'identité que le DME.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function patientAttributes(array $attributes): array
    {
        [$lastName, $firstName] = $this->splitName($attributes);

        return array_filter([
            'last_name' => $lastName,
            'first_name' => $firstName,
            'sex' => $this->normalizeSex($attributes['sex'] ?? null),
            'birth_date' => $this->birthDate($attributes),
            'birth_date_estimated' => $this->birthDateIsEstimated($attributes),
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'address' => $attributes['address'] ?? null,
            'city' => $attributes['city'] ?? null,
            'status' => 'active',
        ], static fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: string, 1: string}
     */
    private function splitName(array $attributes): array
    {
        $lastName = trim((string) ($attributes['last_name'] ?? ''));
        $firstName = trim((string) ($attributes['first_name'] ?? ''));

        if ($lastName !== '' || $firstName !== '') {
            return [$lastName !== '' ? $lastName : '-', $firstName];
        }

        // L'hôte n'a transmis qu'un nom complet : le premier mot est
        // considéré comme le nom de famille, le reste comme le prénom.
        $full = trim((string) ($attributes['name'] ?? ''));

        if ($full === '') {
            throw new InvalidArgumentException(
                "La création d'un patient exige au minimum un nom."
            );
        }

        $parts = preg_split('/\s+/', $full) ?: [$full];
        $lastName = (string) array_shift($parts);

        return [$lastName, implode(' ', $parts)];
    }

    /**
     * Le DME distingue quatre valeurs ; les libellés courants de l'hôte
     * (M/F, homme/femme, male/female) y sont ramenés.
     */
    private function normalizeSex(?string $sex): ?string
    {
        if ($sex === null || trim($sex) === '') {
            return 'unknown';
        }

        return match (mb_strtolower(trim($sex))) {
            'm', 'male', 'homme', 'masculin' => 'male',
            'f', 'female', 'femme', 'feminin', 'féminin' => 'female',
            'o', 'other', 'autre' => 'other',
            default => 'unknown',
        };
    }

    /**
     * Date de naissance : reprise telle quelle si l'hôte la fournit,
     * sinon estimée à partir de l'âge. Une date estimée est marquée
     * comme telle, pour ne jamais faire passer une approximation pour
     * une donnée d'état civil.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function birthDate(array $attributes): ?Carbon
    {
        $birthDate = $attributes['birth_date'] ?? null;

        if ($birthDate !== null && $birthDate !== '') {
            try {
                return Carbon::parse($birthDate)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $age = $attributes['age'] ?? null;

        if ($age === null || $age === '' || ! is_numeric($age)) {
            return null;
        }

        return Carbon::now()->subYears((int) $age)->startOfYear();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function birthDateIsEstimated(array $attributes): ?bool
    {
        $hasBirthDate = ($attributes['birth_date'] ?? null) !== null && $attributes['birth_date'] !== '';
        $hasAge = ($attributes['age'] ?? null) !== null && $attributes['age'] !== '' && is_numeric($attributes['age']);

        if ($hasBirthDate) {
            return false;
        }

        return $hasAge ? true : null;
    }
}
