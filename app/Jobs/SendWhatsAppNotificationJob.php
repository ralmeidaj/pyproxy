<?php

namespace App\Jobs;

use App\Enums\NotificationEvent;
use App\Models\ArDigitalNotification;
use App\Models\Boleto;
use App\Models\NotificationLog;
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
        $boleto = Boleto::with(['tenant.arDigitalConfig'])->find($this->boletoId);

        if (! $boleto || ! $boleto->payer_phone) {
            $this->updateLog('failed', 'Boleto não encontrado ou sem telefone');
            return;
        }

        $event = NotificationEvent::from($this->event);

        // Apenas o evento de emissão tem template WhatsApp definido nesta versão
        if ($event !== NotificationEvent::Issued) {
            Log::info('[WhatsApp] Evento sem template configurado — ignorado', [
                'boleto_id' => $boleto->id,
                'event'     => $this->event,
            ]);
            $this->updateLog('sent');
            return;
        }

        $enabled = config('services.meta_whatsapp.enabled');
        $phoneId = config('services.meta_whatsapp.phone_id');
        $token   = config('services.meta_whatsapp.access_token');

        // Sem credenciais em dev — apenas loga
        if (! $enabled || ! $phoneId || ! $token) {
            Log::info('[WhatsApp] Meta API não configurada — notificação simulada', [
                'boleto_id' => $boleto->id,
                'phone'     => $boleto->payer_phone,
            ]);
            $this->updateLog('sent');
            return;
        }

        $version  = config('services.meta_whatsapp.api_version', 'v19.0');
        $to       = $this->sanitizePhone($boleto->payer_phone);
        $amount   = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
        $dueDate  = $boleto->due_date->format('d/m/Y');
        $linkPdf  = $boleto->pdf_url ?? '';

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'     => 'boleto_notificacao',
                    'language' => ['code' => 'pt_BR'],
                    'components' => [[
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $boleto->payer_name],
                            ['type' => 'text', 'text' => $amount],
                            ['type' => 'text', 'text' => $dueDate],
                            ['type' => 'text', 'text' => $linkPdf],
                            ['type' => 'text', 'text' => $boleto->tenant->name],
                        ],
                    ]],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('[WhatsApp] Falha ao enviar via Meta API', [
                'boleto_id' => $boleto->id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            $this->fail(new \RuntimeException('Meta API retornou ' . $response->status()));
            return;
        }

        // Armazena o wamid para correlacionar com o webhook de entrega (AR Digital)
        $wamid = data_get($response->json(), 'messages.0.id');

        if ($wamid) {
            $config = $boleto->tenant->arDigitalConfig;
            if ($config?->enabled) {
                ArDigitalNotification::where('boleto_id', $boleto->id)
                    ->latest()
                    ->limit(1)
                    ->update(['meta_whatsapp_message_id' => $wamid]);
            }

            Log::info('[WhatsApp] Mensagem enviada via Meta API', [
                'boleto_id' => $boleto->id,
                'wamid'     => $wamid,
            ]);
        }

        $this->updateLog('sent');
    }

    public function failed(\Throwable $e): void
    {
        $this->updateLog('failed', $e->getMessage());
    }

    private function sanitizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // Garante DDI 55 (Brasil)
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
