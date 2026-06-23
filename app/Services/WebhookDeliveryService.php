<?php

namespace App\Services;

use App\Models\Boleto;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDeliveryService
{
    public function __construct(private readonly CryptoService $crypto) {}

    public function dispatch(Boleto $boleto): void
    {
        $config = $boleto->boletoConfig;

        if (! $config?->webhook_url) {
            return;
        }

        $payload = $this->buildPayload($boleto);

        $delivery = WebhookDelivery::create([
            'boleto_id' => $boleto->id,
            'url'       => $config->webhook_url,
            'payload'   => $payload,
            'status'    => 'pending',
        ]);

        $this->attempt($delivery, $config->getWebhookSecret());
    }

    public function attempt(WebhookDelivery $delivery, ?string $secret): void
    {
        $payload   = json_encode($delivery->payload);
        $signature = $secret ? $this->crypto->hmac($payload, $secret) : null;

        $headers = ['Content-Type' => 'application/json'];
        if ($signature) {
            $headers['X-Payproxy-Signature'] = "sha256={$signature}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($delivery->url, $delivery->payload);

            $delivery->update([
                'status'          => $response->successful() ? 'success' : 'failed',
                'attempts'        => $delivery->attempts + 1,
                'last_attempt_at' => now(),
                'next_attempt_at' => $response->successful() ? null : $this->nextRetryAt($delivery->attempts + 1),
                'response_status' => $response->status(),
                'response_body'   => substr($response->body(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook delivery failed', ['delivery_id' => $delivery->id, 'error' => $e->getMessage()]);

            $delivery->update([
                'status'          => 'failed',
                'attempts'        => $delivery->attempts + 1,
                'last_attempt_at' => now(),
                'next_attempt_at' => $this->nextRetryAt($delivery->attempts + 1),
            ]);
        }
    }

    private function buildPayload(Boleto $boleto): array
    {
        return [
            'event'        => 'boleto.paid',
            'id'           => $boleto->id,
            'external_ref' => $boleto->external_ref,
            'status'       => $boleto->status->value,
            'amount_cents' => $boleto->amount_cents,
            'paid_at'      => $boleto->paid_at?->toIso8601String(),
            'paid_amount_cents' => $boleto->paid_amount_cents,
            'paid_channel' => $boleto->paid_channel,
        ];
    }

    // Backoff exponencial: 1min, 5min, 30min (RF-27)
    private function nextRetryAt(int $attempts): ?\Carbon\Carbon
    {
        return match(true) {
            $attempts === 1 => now()->addMinutes(1),
            $attempts === 2 => now()->addMinutes(5),
            $attempts === 3 => now()->addMinutes(30),
            default         => null, // RF-27: mínimo 3 tentativas
        };
    }
}
