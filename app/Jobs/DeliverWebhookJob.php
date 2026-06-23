<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $deliveryId) {}

    public function handle(WebhookDeliveryService $service): void
    {
        $delivery = WebhookDelivery::with('boleto.boletoConfig')->findOrFail($this->deliveryId);

        if ($delivery->status === 'success') {
            return; // idempotente
        }

        $secret = $delivery->boleto->boletoConfig?->getWebhookSecret();
        $service->attempt($delivery, $secret);
    }
}
