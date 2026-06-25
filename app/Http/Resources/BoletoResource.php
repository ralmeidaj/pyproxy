<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BoletoResource',
    properties: [
        new OA\Property(property: 'id',               type: 'integer', example: 42),
        new OA\Property(property: 'nossonumero',       type: 'string',  example: '559353941'),
        new OA\Property(property: 'pedido_numero',     type: 'string',  example: 'NF-2024-001'),
        new OA\Property(property: 'valor',             type: 'string',  example: '150.00'),
        new OA\Property(property: 'vencimento',        type: 'string',  example: '07/30/2026'),
        new OA\Property(property: 'nome_cliente',      type: 'string',  example: 'João da Silva'),
        new OA\Property(property: 'cpf_cliente',       type: 'string',  example: '12345678909'),
        new OA\Property(property: 'email_cliente',     type: 'string',  example: 'joao@example.com'),
        new OA\Property(property: 'linhaDigitavel',    type: 'string',  example: '48190.00003 00005.15055 ...'),
        new OA\Property(property: 'linkBoleto',        type: 'string',  example: 'https://api.pjbank.com.br/boletos/abc123'),
        new OA\Property(property: 'linkpix',           type: 'string',  nullable: true, example: null),
        new OA\Property(property: 'dda_registered',    type: 'boolean', example: true),
        new OA\Property(property: 'status',            type: 'string',  enum: ['pending', 'paid', 'cancelled', 'expired'], example: 'pending'),
        new OA\Property(property: 'status_label',      type: 'string',  example: 'Pendente'),
        new OA\Property(property: 'paid_at',           type: 'string',  nullable: true, example: null),
        new OA\Property(property: 'cancelled_at',      type: 'string',  nullable: true, example: null),
        new OA\Property(property: 'created_at',        type: 'string',  example: '2026-07-01T12:00:00-03:00'),
        new OA\Property(property: 'split',             type: 'array',   items: new OA\Items(properties: [
            new OA\Property(property: 'nome',                 type: 'string', example: 'ORBY TECNOLOGIA'),
            new OA\Property(property: 'valor_fixo',           type: 'string', example: '11.20'),
            new OA\Property(property: 'porcentagem_encargos', type: 'number', example: 0),
        ])),
    ]
)]

class BoletoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Identificação interna
            'id'                  => $this->id,

            // Identificação (espelha PJBank)
            'nossonumero'         => $this->bank_boleto_id,
            'token_facilitador'   => $this->token_facilitador,
            'pedido_numero'       => $this->external_ref,

            // Valor e vencimento
            'valor'               => number_format($this->amount_cents / 100, 2, '.', ''),
            'vencimento'          => $this->due_date?->format('m/d/Y'),

            // Pagador
            'nome_cliente'        => $this->payer_name,
            'cpf_cliente'         => $this->payer_document,
            'email_cliente'       => $this->payer_email,
            'telefone_cliente'    => $this->payer_phone,
            'endereco_cliente'    => $this->payer_address['logradouro'] ?? null,
            'numero_cliente'      => $this->payer_address['numero'] ?? null,
            'complemento_cliente' => $this->payer_address['complemento'] ?? null,
            'bairro_cliente'      => $this->payer_address['bairro'] ?? null,
            'cidade_cliente'      => $this->payer_address['cidade'] ?? null,
            'estado_cliente'      => $this->payer_address['estado'] ?? null,
            'cep_cliente'         => $this->payer_address['cep'] ?? null,

            // Links de cobrança (espelha PJBank)
            'linhaDigitavel'      => $this->digitable_line,
            'linkBoleto'          => $this->pdf_url,
            'linkpix'             => $this->pix_qr_code,
            'dda_registered'      => $this->dda_registered,

            // Split
            'split' => $this->whenLoaded('splits', fn () =>
                $this->splits->map(fn ($s) => [
                    'nome'                 => $s->name,
                    'valor_fixo'           => number_format($s->amount_cents / 100, 2, '.', ''),
                    'porcentagem_encargos' => $s->payee_details['porcentagem_encargos'] ?? 0,
                ])
            ),

            // Status Payproxy (informação adicional)
            'status'              => $this->status->value,
            'status_label'        => $this->status->label(),
            'paid_at'             => $this->paid_at?->toIso8601String(),
            'paid_channel'        => $this->paid_channel,
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),
            'metadata'            => $this->metadata,
            'created_at'          => $this->created_at->toIso8601String(),
        ];
    }
}
