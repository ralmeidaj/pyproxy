<?php

namespace App\Jobs;

use App\Enums\NotificationEvent;
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
        $boleto = Boleto::with('tenant')->find($this->boletoId);

        if (! $boleto || ! $boleto->payer_phone) {
            $this->updateLog('failed', 'Boleto não encontrado ou sem telefone');
            return;
        }

        $evolutionUrl = config('services.evolution_api.url');
        $evolutionKey = config('services.evolution_api.key');
        $instance     = config('services.evolution_api.instance');

        // v1: Evolution API não configurada em dev — apenas log
        if (! $evolutionUrl || ! $evolutionKey) {
            Log::info('[WhatsApp] Notificação não enviada (Evolution API não configurada)', [
                'boleto_id' => $boleto->id,
                'event'     => $this->event,
                'phone'     => $boleto->payer_phone,
            ]);
            $this->updateLog('sent');
            return;
        }

        $event   = NotificationEvent::from($this->event);
        $message = $this->buildMessage($boleto, $event);

        Http::withHeader('apikey', $evolutionKey)
            ->post("{$evolutionUrl}/message/sendText/{$instance}", [
                'number'  => $this->sanitizePhone($boleto->payer_phone),
                'options' => ['delay' => 1200],
                'textMessage' => ['text' => $message],
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

        // Garante DDI 55 (Brasil)
        if (strlen($digits) === 11 || strlen($digits) === 10) {
            $digits = '55' . $digits;
        }

        return $digits . '@s.whatsapp.net';
    }

    private function buildMessage(Boleto $boleto, NotificationEvent $event): string
    {
        $amount  = 'R$ ' . number_format($boleto->amount_cents / 100, 2, ',', '.');
        $dueDate = $boleto->due_date->format('d/m/Y');
        $name    = $boleto->payer_name;

        return match($event) {
            NotificationEvent::Issued    => "Olá, {$name}! Seu boleto no valor de {$amount} foi emitido com vencimento em {$dueDate}.\n\nLinha digitável:\n{$boleto->digitable_line}",
            NotificationEvent::Paid      => "Pagamento confirmado! Recebemos seu pagamento de {$amount}. Obrigado, {$name}!",
            NotificationEvent::Cancelled => "Seu boleto de {$amount} foi cancelado. Dúvidas? Entre em contato conosco.",
            NotificationEvent::DueSoon   => "Atenção, {$name}! Seu boleto de {$amount} vence em 2 dias ({$dueDate}).\n\nLinha digitável:\n{$boleto->digitable_line}",
            NotificationEvent::Overdue   => "Seu boleto de {$amount} com vencimento em {$dueDate} está vencido. Entre em contato para regularização.",
        };
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
