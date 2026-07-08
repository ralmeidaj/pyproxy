<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArDigitalNotification;
use App\Services\ArDigitalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly ArDigitalService $arDigital,
    ) {}

    /**
     * GET — verificação do webhook pela Meta (hub.challenge).
     * A Meta faz um GET com hub.verify_token e hub.challenge quando o webhook é cadastrado.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $configuredToken = config('services.meta_whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $configuredToken) {
            Log::info('[Meta WhatsApp] Webhook verificado com sucesso');
            return response($challenge, 200);
        }

        Log::warning('[Meta WhatsApp] Falha na verificação do webhook', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response()->json(['error' => 'Forbidden'], 403);
    }

    /**
     * POST — recebe eventos da Meta (delivered, read, failed, etc.).
     * Registra evento AR Digital quando uma mensagem é entregue.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('[Meta WhatsApp] Webhook recebido', ['payload' => $payload]);

        // Itera sobre as mudanças de status da mensagem
        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    $this->processarStatus($status);
                }
            }
        }

        // A Meta exige HTTP 200 — qualquer outro código faz reenvio
        return response()->json(['ok' => true]);
    }

    private function processarStatus(array $status): void
    {
        $wamid      = $status['id'] ?? null;
        $statusCode = $status['status'] ?? null;

        if (! $wamid || ! in_array($statusCode, ['delivered', 'failed'], true)) {
            return;
        }

        $notification = ArDigitalNotification::where('meta_whatsapp_message_id', $wamid)->first();

        if (! $notification) {
            Log::debug('[Meta WhatsApp] wamid não encontrado em ar_digital_notifications', ['wamid' => $wamid]);
            return;
        }

        if ($statusCode === 'delivered') {
            // Idempotência — não registra se já entregue/mais avançado pelo canal whatsapp
            $jaEntregue = $notification->events()
                ->where('tipo', 'entrega_provedor')
                ->where('canal', 'whatsapp')
                ->exists();

            if ($jaEntregue) {
                return;
            }

            $this->arDigital->processarEvento($notification, 'entrega_provedor', 'whatsapp', [
                'smtp_code'     => '200',
                'smtp_response' => 'WhatsApp delivered — Meta webhook',
            ]);

            Log::info('[Meta WhatsApp] Evento entrega_provedor registrado', [
                'notification_id' => $notification->id,
                'wamid'           => $wamid,
            ]);
        }

        if ($statusCode === 'failed') {
            $jaFalhou = $notification->events()
                ->where('tipo', 'bounce')
                ->where('canal', 'whatsapp')
                ->exists();

            if ($jaFalhou) {
                return;
            }

            $errorCode = data_get($status, 'errors.0.code', '');
            $errorMsg  = data_get($status, 'errors.0.message', 'Falha no envio WhatsApp');

            $this->arDigital->processarEvento($notification, 'bounce', 'whatsapp', [
                'smtp_code'     => (string) $errorCode,
                'smtp_response' => $errorMsg,
            ]);

            Log::warning('[Meta WhatsApp] Evento bounce registrado', [
                'notification_id' => $notification->id,
                'wamid'           => $wamid,
                'error'           => $errorMsg,
            ]);
        }
    }
}
