<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Services\BoletoService;
use App\Services\CryptoService;
use App\Services\WebhookDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly BoletoService          $boletoService,
        private readonly WebhookDeliveryService $webhookDelivery,
        private readonly CryptoService          $crypto,
    ) {}

    // Recebe notificação de pagamento do PJBank (RF-23, RNF-09)
    public function pjbank(Request $request): JsonResponse
    {
        $payload = $request->all();

        // PJBank envia o nosso_numero como identificador do boleto
        $bankBoletoId = $payload['nosso_numero'] ?? $payload['token_transaction'] ?? null;

        if (! $bankBoletoId) {
            Log::warning('PJBank webhook recebido sem nosso_numero', $payload);
            return response()->json(['ok' => true]); // 200 para evitar retry do PJBank
        }

        $boleto = Boleto::where('bank_boleto_id', $bankBoletoId)->first();

        if (! $boleto) {
            Log::warning("PJBank webhook: boleto não encontrado para bank_boleto_id={$bankBoletoId}");
            return response()->json(['ok' => true]);
        }

        // RF-26: idempotência — notificação duplicada ignorada
        if ($boleto->status->isFinal()) {
            return response()->json(['ok' => true]);
        }

        $paidAmountCents = (int) round(($payload['valor'] ?? 0) * 100);
        $channel         = $payload['forma_pagamento'] ?? 'barcode';
        $paidAt          = now();

        $this->boletoService->markAsPaid($boleto, $paidAmountCents, $channel, $paidAt);

        // RF-25: retransmite webhook ao tenant
        $this->webhookDelivery->dispatch($boleto->fresh());

        return response()->json(['ok' => true]);
    }
}
