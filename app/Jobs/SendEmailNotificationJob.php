<?php

namespace App\Jobs;

use App\Enums\NotificationEvent;
use App\Mail\BoletoNotificationMail;
use App\Models\ArDigitalNotification;
use App\Models\Boleto;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
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
        $boleto = Boleto::with(['boletoConfig', 'tenant.arDigitalConfig'])->find($this->boletoId);

        if (! $boleto || ! $boleto->payer_email) {
            $this->updateLog('failed', 'Boleto não encontrado ou sem e-mail');
            return;
        }

        $event = NotificationEvent::from($this->event);

        // Carrega notificação AR Digital se o evento for de emissão e o tenant tiver AR ativo
        $arNotification = null;
        $pixelTracking  = false;

        if ($event === NotificationEvent::Issued) {
            $config = $boleto->tenant->arDigitalConfig;
            if ($config?->enabled) {
                $arNotification = ArDigitalNotification::where('boleto_id', $boleto->id)
                    ->latest()
                    ->first();
                $pixelTracking = $config->pixel_tracking;
            }
        }

        Mail::to($boleto->payer_email)
            ->send(new BoletoNotificationMail($boleto, $event, $arNotification, $pixelTracking));

        $this->updateLog('sent');
    }

    public function failed(\Throwable $e): void
    {
        $this->updateLog('failed', $e->getMessage());
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
