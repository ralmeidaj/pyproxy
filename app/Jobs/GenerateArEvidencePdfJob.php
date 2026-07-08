<?php

namespace App\Jobs;

use App\Models\ArDigitalNotification;
use App\Services\ArEvidencePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateArEvidencePdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int $notificationId,
    ) {}

    public function handle(ArEvidencePdfService $pdfService): void
    {
        $notification = ArDigitalNotification::with([
            'boleto.tenant',
            'events.timestamp',
        ])->find($this->notificationId);

        if (! $notification) {
            Log::warning('PDF Job: notificação não encontrada', [
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        // Aguarda todos os eventos terem seus carimbos antes de gerar o laudo
        $eventosSemCarimbo = $notification->events()
            ->whereNull('tsr_path')
            ->count();

        if ($eventosSemCarimbo > 0) {
            // Re-enfileira com delay para aguardar os carimbos pendentes
            self::dispatch($this->notificationId)->delay(now()->addSeconds(30));
            return;
        }

        $laudoPath = $pdfService->gerar($notification);

        $notification->update(['laudo_path' => $laudoPath]);

        Log::info('PDF Job: laudo gerado', [
            'notification_id' => $notification->id,
            'laudo_path'      => $laudoPath,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('PDF Job: falha definitiva', [
            'notification_id' => $this->notificationId,
            'error'           => $e->getMessage(),
        ]);
    }
}
