<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\BoletoPaid;
use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Services\BoletoService;
use App\Services\WebhookDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WebhookController extends Controller
{
    public function __construct(
        private readonly BoletoService          $boletoService,
        private readonly WebhookDeliveryService $webhookDelivery,
    ) {}

    #[OA\Post(
        path: '/webhooks/pjbank',
        summary: 'Webhook de pagamento PJBank',
        description: 'Endpoint chamado pelo PJBank para notificar pagamentos. Não é chamado pelos tenants. A autenticidade é validada via HMAC-SHA256 (header `X-PJBank-Hmac-Sha256`) e pela credencial do boleto. Retorna 200 mesmo em cenários de idempotência para evitar retentativas do PJBank.',
        tags: ['Webhooks'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nosso_numero',      type: 'string',  example: '99999999',       description: 'Identificador do boleto no PJBank'),
                    new OA\Property(property: 'token_transaction', type: 'string',  example: 'tok_abc123',     description: 'Token alternativo de identificação'),
                    new OA\Property(property: 'credencial',        type: 'string',  example: 'cred_xxxxx',     description: 'Credencial do tenant no PJBank (validada fail-closed)'),
                    new OA\Property(property: 'valor',             type: 'number',  example: 150.00,           description: 'Valor pago em reais'),
                    new OA\Property(property: 'forma_pagamento',   type: 'string',  example: 'pix',            description: 'Canal de pagamento: barcode, pix, ted, etc.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificação processada (ou ignorada por idempotência)',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'ok', type: 'boolean', example: true)]
                )
            ),
            new OA\Response(response: 401, description: 'Assinatura HMAC inválida ou credencial incorreta/ausente'),
        ]
    )]
    // Validação de autenticidade feita pelo HmacWebhookMiddleware (RF-23, RNF-09)
    public function pjbank(Request $request): JsonResponse
    {
        /** @var Boleto $boleto */
        $boleto  = $request->attributes->get('resolved_boleto');
        $payload = $request->all();

        // RF-26: idempotência — notificação duplicada ignorada
        if ($boleto->status->isFinal()) {
            return response()->json(['ok' => true]);
        }

        $paidAmountCents = (int) round(($payload['valor'] ?? 0) * 100);
        $channel         = $payload['forma_pagamento'] ?? 'barcode';
        $paidAt          = now();

        $this->boletoService->markAsPaid($boleto, $paidAmountCents, $channel, $paidAt);

        BoletoPaid::dispatch($boleto->tenant_id, $paidAmountCents, $boleto->external_ref);

        // RF-25: retransmite webhook ao tenant
        $this->webhookDelivery->dispatch($boleto->fresh());

        Log::info('PJBank webhook: pagamento registrado', [
            'boleto_id'       => $boleto->id,
            'external_ref'    => $boleto->external_ref,
            'paid_amount'     => $paidAmountCents,
            'channel'         => $channel,
        ]);

        return response()->json(['ok' => true]);
    }
}
