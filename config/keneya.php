<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuration métier Keneya-DME
|--------------------------------------------------------------------------
|
| Ce fichier centralise les valeurs métier de l'application afin d'éviter
| toute valeur codée en dur dans le code applicatif. Lors de la phase 2
| (transformation en module), ce fichier deviendra le fichier de
| configuration publiable du module.
|
*/

return [

    /*
    | Version fonctionnelle de l'application (§63). Affichée dans
    | l'interface et dans les documents générés.
    */
    'version' => '0.1.0',

    /*
    | Établissement de santé exploitant l'application. Ces valeurs sont
    | utilisées dans l'entête de l'interface et dans les documents PDF.
    */
    'facility' => [
        'name' => env('KENEYA_FACILITY_NAME', 'Centre Hospitalier Keneya'),
        'address' => env('KENEYA_FACILITY_ADDRESS', 'Bamako, Mali'),
        'phone' => env('KENEYA_FACILITY_PHONE', '+223 20 00 00 00'),
        'email' => env('KENEYA_FACILITY_EMAIL', 'contact@keneya.test'),
    ],

    /*
    | Préfixes des identifiants métier lisibles (§37 de la spécification).
    | Format généré : <PREFIXE>-<ANNEE>-<SEQUENCE SUR 6 CHIFFRES>.
    | Ces identifiants sont stables : ils ne sont jamais recalculés après
    | création et servent de clé de correspondance lors d'une future
    | intégration FHIR / HL7.
    */
    'identifiers' => [
        'padding' => 6,
        'prefixes' => [
            'patient' => 'PAT',
            'consultation' => 'CONS',
            'prescription' => 'ORD',
            'lab_order' => 'LAB',
            'imaging_order' => 'IMG',
            'hospitalization' => 'HOSP',
            'document' => 'DOC',
            'appointment' => 'RDV',
            'care_order' => 'SOIN',
        ],
    ],

    /*
    | Pagination par défaut des listes (§58 — performance).
    */
    'pagination' => [
        'default' => 15,
        'timeline' => 20,
    ],

    /*
    | Stockage des documents médicaux. Le disque doit rester privé :
    | aucun document n'est servi directement depuis le système de fichiers,
    | tout téléchargement passe par une route contrôlée (§42).
    */
    'documents' => [
        'disk' => 'local',
        'directory' => 'medical-documents',
        'max_size_kb' => 20480,
        'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'dcm', 'txt'],
    ],

    /*
    | Seuils cliniques utilisés pour signaler une constante hors norme.
    | Purement indicatif : l'application n'établit aucun diagnostic.
    */
    'vitals' => [
        'temperature' => ['min' => 36.0, 'max' => 37.5],
        'heart_rate' => ['min' => 60, 'max' => 100],
        'respiratory_rate' => ['min' => 12, 'max' => 20],
        'oxygen_saturation' => ['min' => 95, 'max' => 100],
        'systolic' => ['min' => 90, 'max' => 139],
        'diastolic' => ['min' => 60, 'max' => 89],
        'glycemia' => ['min' => 0.7, 'max' => 1.1],
    ],

    /*
    | Données de démonstration. Le seeder de démonstration refuse de
    | s'exécuter en dehors des environnements local et testing.
    */
    'demo' => [
        'enabled' => env('DEMO_SEED_ENABLED', false),
        'password' => env('DEMO_USER_PASSWORD'),
    ],
];
