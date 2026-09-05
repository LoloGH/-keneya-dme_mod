<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\Sms\Gateways\HttpGateway;
use App\Services\Sms\Gateways\LogGateway;
use App\Services\Sms\Gateways\ArrayGateway;
use InvalidArgumentException;

/**
 * Résout la passerelle SMS active à partir de config/sms.php (§35).
 */
class SmsGatewayManager
{
    /** @var array<string, SmsGateway> */
    private array $resolved = [];

    public function gateway(?string $name = null): SmsGateway
    {
        $name ??= (string) (config('sms.driver') ?: 'log');

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    private function resolve(string $name): SmsGateway
    {
        $config = config("sms.gateways.{$name}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Passerelle SMS « {$name} » non configurée.");
        }

        return match ($config['driver'] ?? $name) {
            'log' => new LogGateway($config),
            'array' => new ArrayGateway(),
            'http' => new HttpGateway($config),
            default => throw new InvalidArgumentException(
                "Pilote de passerelle SMS « {$config['driver']} » inconnu."
            ),
        };
    }

    /**
     * Enregistre une passerelle personnalisée (utile aux tests et à une
     * future extension par un opérateur local).
     */
    public function extend(string $name, SmsGateway $gateway): void
    {
        $this->resolved[$name] = $gateway;
    }
}
