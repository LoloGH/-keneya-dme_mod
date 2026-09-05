<?php

declare(strict_types=1);

namespace App\Services\Sms;

/**
 * Contrat d'une passerelle SMS (§35).
 *
 * Ajouter un opérateur revient à implémenter cette interface et à
 * déclarer sa configuration dans config/sms.php — aucun code métier n'est
 * à modifier.
 */
interface SmsGateway
{
    /**
     * Envoie un message à un destinataire au format E.164.
     */
    public function send(string $recipient, string $body, ?string $sender = null): SmsResult;

    /**
     * Nom court de la passerelle, journalisé avec chaque message.
     */
    public function name(): string;
}
