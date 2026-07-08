<?php

namespace App\Jobs;

use App\Models\ArDigitalNotification;
use App\Services\ArDigitalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSmtpDsnJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 600];

    public function __construct(
        private readonly string $token,
        private readonly string $smtpCode,
        private readonly string $smtpResponse,
    ) {}

    public function handle(ArDigitalService $arDigital): void
    {
        $notification = ArDigitalNotification::where('token', $this->token)->first();

        if (! $notification) {
            Log::warning('DSN Job: notificação não encontrada', ['token' => $this->token]);
            return;
        }

        // Já processada — idempotência
        if (in_array($notification->status, ['entregue', 'lido', 'confirmado', 'bounce'])) {
            return;
        }

        $code = (int) $this->smtpCode;

        // 2xx = entregue ao servidor do destinatário
        // 5xx = bounce permanente (endereço inválido, caixa cheia, etc.)
        $tipo = ($code >= 200 && $code < 300) ? 'entrega_provedor' : 'bounce';

        $arDigital->processarEvento($notification, $tipo, 'email', [
            'smtp_code'     => $this->smtpCode,
            'smtp_response' => $this->smtpResponse,
        ]);

        Log::info('DSN Job: evento registrado', [
            'token'      => $this->token,
            'tipo'       => $tipo,
            'smtp_code'  => $this->smtpCode,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DSN Job: falha definitiva', [
            'token' => $this->token,
            'error' => $e->getMessage(),
        ]);
    }
}
