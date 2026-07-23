<?php

namespace App\Jobs;

use App\Enums\CommunicationModel;
use App\Enums\NotificationEvent;
use App\Models\Boleto;
use App\Models\NotificationLog;
use App\Models\WhatsappConsent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int $boletoId,
        private readonly string $event,
        private readonly ?int $logId = null,
    ) {}

    public function handle(): void
    {
        $boleto = Boleto::with('tenant')->find($this->boletoId);

        if (! $boleto || ! $boleto->payer_phone) {
            $this->updateLog('failed', 'Boleto não encontrado ou sem telefone');
            return;
        }

        // Verificar consentimento LGPD para tenants com WhatsApp ativo
        if ($boleto->tenant?->communication_model === CommunicationModel::EmailWhatsApp) {
            if (! $boleto->payer_document) {
                Log::info('[WhatsApp] Pagador sem CPF/CNPJ — consentimento não verificável, envio abortado', [
                    'boleto_id' => $boleto->id,
                    'tenant_id' => $boleto->tenant_id,
                ]);
                return;
            }

            if (! WhatsappConsent::hasActiveConsent($boleto->tenant_id, $boleto->payer_document)) {
                Log::info('[WhatsApp] Sem consentimento LGPD — envio pulado', [
                    'boleto_id' => $boleto->id,
                    'tenant_id' => $boleto->tenant_id,
                ]);
                return;
            }
        }

        $event = NotificationEvent::from($this->event);

        if ($event !== NotificationEvent::Issued) {
            Log::info('[WhatsApp] Evento sem template configurado — ignorado', [
                'boleto_id' => $boleto->id,
                'event'     => $this->event,
            ]);
            $this->updateLog('sent');
            return;
        }

        $enabled = config('services.ovc360.enabled');
        $key     = config('services.ovc360.integration_key');
        $url     = config('services.ovc360.endpoint');

        if (! $enabled || ! $key) {
            Log::info('[WhatsApp] OVC360 não configurada — notificação simulada', [
                'boleto_id' => $boleto->id,
                'phone'     => $boleto->payer_phone,
            ]);
            $this->updateLog('sent');
            return;
        }

        $payload = [
            'name'       => $boleto->payer_name,
            'phone'      => $this->sanitizePhone($boleto->payer_phone),
            'invoice_id' => basename(parse_url($boleto->pdf_url ?? '', PHP_URL_PATH)),
            'due_date'   => $boleto->due_date->format('d/m'),
            'price'      => 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.'),
        ];

        if ($boleto->payer_email) {
            $payload['email'] = $boleto->payer_email;
        }

        $response = Http::withHeader('X-Integration-Key', $key)
            ->post($url, $payload);

        if (! $response->successful() || ! $response->json('received')) {
            Log::error('[WhatsApp] Falha ao enviar via OVC360', [
                'boleto_id' => $boleto->id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            $this->fail(new \RuntimeException('OVC360 retornou ' . $response->status()));
            return;
        }

        Log::info('[WhatsApp] Mensagem enviada via OVC360', [
            'boleto_id'  => $boleto->id,
            'invoice_id' => $boleto->bank_boleto_id,
            'phone'      => $payload['phone'],
        ]);

        $this->updateLog('sent');
    }

    public function failed(\Throwable $e): void
    {
        $this->updateLog('failed', $e->getMessage());
    }

    private function sanitizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 || strlen($digits) === 10) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    private function updateLog(string $status, ?string $error = null): void
    {
        if (! $this->logId) {
            return;
        }

        NotificationLog::where('id', $this->logId)->update(array_filter([
            'status'  => $status,
            'error'   => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ], fn ($v) => $v !== null));
    }
}
