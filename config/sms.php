<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Service SMS transversal
|--------------------------------------------------------------------------
|
| Le service SMS est volontairement découplé du domaine médical : il ne
| connaît ni patient, ni consultation. Cette séparation permettra de
| l'extraire tel quel lors de la phase 2 pour en faire un service SMS
| partagé de Keneya Workflow.
|
| Passerelle de production : SMSGate (SMS Gateway for Android™).
| Les passerelles « log » et « array » n'existent que pour le
| développement et les tests : elles n'émettent aucun SMS réel.
|
*/

return [

    /*
    | Passerelle active.
    |
    | `?:` et non un défaut d'env : Laravel convertit la chaîne « null »
    | en PHP null, ce qui laisserait la passerelle non résolue.
    */
    'gateway' => env('SMS_GATEWAY') ?: 'log',

    'sender' => env('SMS_SENDER', 'Keneya'),

    /*
    | File d'attente : les SMS y transitent toujours, afin que
    | l'indisponibilité d'une passerelle ne bloque jamais un acte médical.
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

    /*
    | Suivi d'acheminement : SMSGate accuse d'abord réception du message
    | (« accepté »), puis notifie son envoi et sa remise. La commande
    | `keneya:sms:refresh` interroge la passerelle pour les messages
    | encore en transit.
    */
    'status_tracking' => [
        'enabled' => (bool) env('SMS_STATUS_TRACKING', true),
        // Fenêtre au-delà de laquelle un message non finalisé n'est plus interrogé.
        'max_age_hours' => (int) env('SMS_STATUS_MAX_AGE_HOURS', 48),
        'batch_size' => (int) env('SMS_STATUS_BATCH_SIZE', 100),
    ],

    'gateways' => [

        /*
        | SMSGate — passerelle de production.
        |
        | Deux modes d'exploitation, tous deux couverts par cette
        | configuration :
        |   - cloud   : https://api.sms-gate.app/3rdparty/v1
        |   - local   : http://<ip-de-l-appareil>:8080/3rdparty/v1
        |
        | Authentification HTTP Basic (identifiant + mot de passe) ou
        | Bearer si seul un jeton est fourni. Aucun secret n'est stocké
        | dans le dépôt : tout provient de l'environnement.
        */
        'smsgate' => [
            'driver' => 'smsgate',
            'base_url' => env('SMSGATE_BASE_URL', 'https://api.sms-gate.app/3rdparty/v1'),
            'username' => env('SMSGATE_USERNAME'),
            'password' => env('SMSGATE_PASSWORD'),
            'token' => env('SMSGATE_TOKEN'),
            'sender' => env('SMSGATE_SENDER'),
            // Carte SIM à utiliser sur l'appareil (null = choix de l'appareil).
            'sim_number' => env('SMSGATE_SIM_NUMBER') ? (int) env('SMSGATE_SIM_NUMBER') : null,
            'with_delivery_report' => (bool) env('SMSGATE_DELIVERY_REPORT', true),
            'timeout' => (int) env('SMSGATE_TIMEOUT', 15),
            // Durée de validité du message côté passerelle, en secondes.
            'ttl' => env('SMSGATE_TTL') ? (int) env('SMSGATE_TTL') : null,
            'verify_tls' => (bool) env('SMSGATE_VERIFY_TLS', true),
        ],

        /*
        | Développement : aucun envoi réel, le message est journalisé.
        | L'interface signale explicitement qu'il s'agit d'une simulation.
        */
        'log' => [
            'driver' => 'log',
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        /*
        | Tests automatisés : accepte tout, n'émet rien.
        */
        'array' => [
            'driver' => 'array',
        ],
    ],

    /*
    | Indicatif appliqué aux numéros saisis au format national.
    */
    'default_country_code' => env('SMS_COUNTRY_CODE', '+223'),
];
