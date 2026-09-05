<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Jobs\SendSmsMessage;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service SMS transversal (§35, §53).
 *
 * Point d'entrée unique de tout envoi. Le flux est toujours le même :
 *
 *   événement métier → SmsService → file d'attente → passerelle
 *   → statut → historique
 *
 * Un message est d'abord persisté (statut « pending »), puis mis en file.
 * Ainsi, aucun SMS n'est perdu si la passerelle est indisponible, et
 * l'échec d'un envoi ne peut jamais interrompre un acte médical.
 *
 * Découplage volontaire : ce service ne connaît ni Patient, ni Rendez-vous.
 * Il reçoit un numéro, un texte et un contexte optionnel (type + id) sous
 * forme scalaire. C'est ce qui permettra de l'extraire en phase 2 pour en
 * faire le service SMS partagé de Keneya Workflow.
 */
class SmsService
{
    public function __construct(private readonly SmsGatewayManager $gateways)
    {
    }

    /**
     * Prépare et met en file un message libre.
     *
     * @param  array{patient_id?: int|null, context_type?: string|null, context_id?: int|null}  $context
     */
    public function send(
        string $recipient,
        string $body,
        array $context = [],
        ?Carbon $scheduledFor = null,
        ?SmsTemplate $template = null,
    ): SmsMessage {
        $normalized = PhoneNumber::normalize($recipient);

        if ($normalized === null) {
            throw new RuntimeException('Numéro de destinataire invalide.');
        }

        $message = SmsMessage::create([
            'reference' => 'SMS-'.Str::upper(Str::random(10)),
            'recipient' => $normalized,
            'body' => $body,
            'sender' => config('sms.sender'),
            'sms_template_id' => $template?->getKey(),
            'patient_id' => $context['patient_id'] ?? null,
            'context_type' => $context['context_type'] ?? null,
            'context_id' => $context['context_id'] ?? null,
            'status' => 'pending',
            'scheduled_for' => $scheduledFor,
            'created_by' => Auth::id(),
        ]);

        return $this->dispatch($message);
    }

    /**
     * Envoie un message construit à partir d'un modèle de texte (§36).
     *
     * @param  array<string, string|int|null>  $variables
     * @param  array{patient_id?: int|null, context_type?: string|null, context_id?: int|null}  $context
     */
    public function sendTemplate(
        string $templateKey,
        string $recipient,
        array $variables = [],
        array $context = [],
        ?Carbon $scheduledFor = null,
    ): ?SmsMessage {
        $template = SmsTemplate::where('key', $templateKey)->where('is_active', true)->first();

        // Un modèle absent ou désactivé n'est pas une erreur bloquante :
        // la notification métier a déjà eu lieu, seul le SMS est omis.
        if ($template === null) {
            return null;
        }

        return $this->send(
            $recipient,
            $template->render($variables),
            $context,
            $scheduledFor,
            $template,
        );
    }

    /**
     * Place le message dans la file d'attente configurée.
     */
    public function dispatch(SmsMessage $message): SmsMessage
    {
        $message->update(['status' => 'queued']);

        $job = SendSmsMessage::dispatch($message->id)
            ->onQueue((string) config('sms.queue.name', 'sms'));

        if ($connection = config('sms.queue.connection')) {
            $job->onConnection($connection);
        }

        if ($message->scheduled_for !== null && $message->scheduled_for->isFuture()) {
            $job->delay($message->scheduled_for);
        }

        return $message->refresh();
    }

    /**
     * Envoi effectif auprès de la passerelle. Appelé par le job ; ne doit
     * pas être appelé directement depuis un contrôleur.
     */
    public function deliver(SmsMessage $message): SmsResult
    {
        $gateway = $this->gateways->gateway();

        $result = $gateway->send($message->recipient, $message->body, $message->sender);

        $message->forceFill([
            'attempts' => $message->attempts + 1,
            'gateway' => $result->gateway,
            'gateway_response' => $result->response,
        ]);

        if ($result->successful) {
            $message->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'gateway_message_id' => $result->messageId,
                'error_message' => null,
            ]);
        } else {
            $message->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $result->error,
            ]);
        }

        $message->save();

        return $result;
    }

    /**
     * Rejoue un message en échec, dans la limite du quota d'essais.
     */
    public function retry(SmsMessage $message): SmsMessage
    {
        if (! $message->isRetryable()) {
            throw new RuntimeException(
                'Ce message a atteint le nombre maximal de tentatives et ne peut plus être rejoué.'
            );
        }

        $message->update(['status' => 'pending', 'error_message' => null]);

        return $this->dispatch($message);
    }
}
