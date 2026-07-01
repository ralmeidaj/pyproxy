<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoletoIssued implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $amountCents,
        public readonly string $payerName,
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
        return 'boleto.issued';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id'    => $this->tenantId,
            'amount_cents' => $this->amountCents,
            'payer_name'   => $this->payerName,
            'external_ref' => $this->externalRef,
        ];
    }
}
