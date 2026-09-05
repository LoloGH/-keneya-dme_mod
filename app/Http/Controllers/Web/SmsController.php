<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Service SMS (§35) : historique, statuts, envoi manuel et réessai.
 *
 * L'écran expose le fonctionnement du service transversal : passerelle
 * active, file d'attente, tentatives et erreurs. En configuration par
 * défaut la passerelle est « log » : aucun SMS réel n'est émis.
 */
class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SmsMessage::class);

        $messages = SmsMessage::query()
            ->with(['patient:id,patient_number,first_name,last_name', 'template:id,name'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(fn ($inner) => $inner
                ->where('recipient', 'like', '%'.$term.'%')
                ->orWhere('reference', 'like', '%'.$term.'%')))
            ->orderByDesc('created_at')
            ->paginate(config('keneya.pagination.default'))
            ->withQueryString();

        return view('sms.index', [
            'messages' => $messages,
            'filters' => $request->only(['q', 'status']),
            'templates' => SmsTemplate::orderBy('name')->get(),
            'gateway' => config('sms.driver'),
            'stats' => [
                'sent' => SmsMessage::where('status', 'sent')->count(),
                'queued' => SmsMessage::whereIn('status', ['pending', 'queued'])->count(),
                'failed' => SmsMessage::where('status', 'failed')->count(),
            ],
        ]);
    }

    public function store(Request $request, SmsService $sms): RedirectResponse
    {
        $this->authorize('create', SmsMessage::class);

        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:30'],
            'body' => ['required', 'string', 'max:480'],
            'patient_id' => ['nullable', 'exists:patients,id'],
        ], [], [
            'recipient' => 'destinataire',
            'body' => 'message',
        ]);

        try {
            $sms->send(
                recipient: $data['recipient'],
                body: $data['body'],
                context: ['patient_id' => $data['patient_id'] ?? null],
            );
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['recipient' => $exception->getMessage()]);
        }

        return back()->with('success', 'Message placé dans la file d’envoi.');
    }

    public function retry(SmsMessage $smsMessage, SmsService $sms): RedirectResponse
    {
        $this->authorize('retry', $smsMessage);

        try {
            $sms->retry($smsMessage);
        } catch (Throwable $exception) {
            return back()->withErrors(['sms' => $exception->getMessage()]);
        }

        return back()->with('success', 'Message replacé dans la file d’envoi.');
    }
}
