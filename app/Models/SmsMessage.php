<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message SMS et son statut d'acheminement (§35).
 *
 * Le lien vers le patient et vers l'objet métier est volontairement
 * faible (colonnes non contraintes) : le service SMS ne dépend d'aucun
 * modèle du DME.
 */
class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'recipient', 'body', 'sender', 'sms_template_id',
        'patient_id', 'context_type', 'context_id', 'status', 'attempts',
        'scheduled_for', 'sent_at', 'failed_at', 'error_message',
        'gateway', 'gateway_message_id', 'gateway_response', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'gateway_response' => 'array',
            'attempts' => 'integer',
        ];
    }

    /** @var array<string, string> */
    public const STATUSES = [
        'pending' => 'En attente',
        'queued' => 'Dans la file',
        'sent' => 'Envoyé',
        'failed' => 'Échec',
        'cancelled' => 'Annulé',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Un message en échec peut être rejoué tant que le quota d'essais reste ouvert. */
    public function isRetryable(): bool
    {
        return $this->status === 'failed'
            && $this->attempts < (int) config('sms.retry.max_attempts');
    }
}
