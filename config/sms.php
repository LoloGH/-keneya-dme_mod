<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Service SMS transversal
|--------------------------------------------------------------------------
|
| Le service SMS est volontairement découplé du domaine médical (§35).
| Il ne connaît ni patient, ni consultation : il reçoit un destinataire,
| un contenu et un contexte optionnel. Cette séparation permettra de
| l'extraire tel quel lors de la phase 2 pour en faire un service SMS
| partagé de Keneya Workflow.
|
*/

return [

    /*
    | Passerelle active. « log » n'envoie rien et journalise le message :
    | c'est le comportement par défaut, y compris pour la démonstration.
    */
    // `?:` et non un défaut d'env : Laravel convertit la chaîne « null »
    // en PHP null, ce qui laisserait la passerelle non résolue.
    'driver' => env('SMS_DRIVER') ?: 'log',

    'sender' => env('SMS_SENDER', 'Keneya'),

    /*
    | File d'attente : les SMS sont toujours passés par la file afin que
    | l'échec d'une passerelle ne bloque jamais un acte médical.
    */
    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION'),
        'name' => env('SMS_QUEUE', 'sms'),
    ],

    /*
    | Politique de réessai en cas d'échec de la passerelle.
    */
    'retry' => [
        'max_attempts' => (int) env('SMS_MAX_ATTEMPTS', 3),
        'delay_seconds' => (int) env('SMS_RETRY_DELAY', 60),
    ],

    'gateways' => [

        'log' => [
            'driver' => 'log',
            'channel' => env('SMS_LOG_CHANNEL', 'stack'),
        ],

        // Passerelle inerte utilisée par la suite de tests : elle accepte
        // tout et n'émet rien.
        'array' => [
            'driver' => 'array',
        ],

        'http' => [
            'driver' => 'http',
            'endpoint' => env('SMS_HTTP_ENDPOINT'),
            'token' => env('SMS_HTTP_TOKEN'),
            'timeout' => (int) env('SMS_HTTP_TIMEOUT', 10),
        ],
    ],

    /*
    | Indicatif par défaut appliqué aux numéros saisis en format national.
    */
    'default_country_code' => env('SMS_COUNTRY_CODE', '+223'),
];
