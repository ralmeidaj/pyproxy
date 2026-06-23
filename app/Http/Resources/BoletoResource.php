<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoletoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
