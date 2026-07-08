<?php

namespace App\Jobs;

use App\Models\ArDigitalEvent;
use App\Services\Rfc3161TimestampService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ApplyRfc3161TimestampJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly int $eventId,
    ) {}

    public function handle(Rfc3161TimestampService $rfc3161): void
    {
        $event = ArDigitalEvent::with(['notification.tenant.arDigitalConfig'])
            ->find($this->eventId);

        if (! $event) {
            Log::warning('RFC3161 Job: evento não encontrado', ['event_id' => $this->eventId]);
            return;
        }

        // Evita carimbar o mesmo evento duas vezes
        if ($event->timestamp()->exists()) {
            return;
        }

        $timestamp = $rfc3161->carimbar($event);

        if ($timestamp) {
            Log::info('RFC3161 Job: carimbo aplicado', [
                'event_id'      => $event->id,
                'tipo'          => $event->tipo,
                'act_provider'  => $timestamp->act_provider,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RFC3161 Job: falha definitiva após todas as tentativas', [
            'event_id' => $this->eventId,
            'error'    => $e->getMessage(),
        ]);
    }
}
