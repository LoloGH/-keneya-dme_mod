<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Résultat d'un envoi tenté auprès d'une passerelle.
 *
 * Objet immuable appartenant au service SMS : il ne dépend d'aucun modèle
 * du domaine médical, ce qui permettra d'extraire le service tel quel en
 * phase 2 (§35).
 */
final readonly class SmsResult
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public bool $successful,
        public string $gateway,
        public ?string $messageId = null,
        public ?string $error = null,
        public array $response = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function success(string $gateway, ?string $messageId = null, array $response = []): self
    {
        return new self(true, $gateway, $messageId, null, $response);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function failure(string $gateway, string $error, array $response = []): self
    {
        return new self(false, $gateway, null, $error, $response);
    }
}
