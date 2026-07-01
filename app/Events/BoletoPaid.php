<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoletoPaid implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $paidAmountCents,
        public readonly string $externalRef,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.backoffice'),
            new PrivateChannel("dashboard.{$this->tenantId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'boleto.paid';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'paid_amount_cents' => $this->paidAmountCents,
            'external_ref'      => $this->externalRef,
        ];
    }
}
