<?php

namespace App\DTOs;

use App\Http\Requests\Api\V1\IssueBoletoRequest;

final readonly class IssueBoletoData
{
    public function __construct(
        public string  $externalRef,
        public int     $amountCents,
        public string  $dueDate,
        public string  $payerName,
        public string  $payerDocument,
        public ?string $payerEmail    = null,
        public ?string $payerPhone    = null,
        public ?array  $payerAddress  = null,
        public ?array  $metadata      = [],
    ) {}

    public static function fromRequest(IssueBoletoRequest $request): self
    {
        return new self(
            externalRef:   $request->pedido_numero,
            amountCents:   (int) round((float) $request->valor * 100),
            dueDate:       \Carbon\Carbon::createFromFormat('m/d/Y', $request->vencimento)->toDateString(),
            payerName:     $request->nome_cliente,
            payerDocument: $request->cpf_cliente,
            payerEmail:    $request->email_cliente,
            payerPhone:    $request->telefone_cliente,
            payerAddress:  [
                'logradouro'  => $request->endereco_cliente,
                'numero'      => $request->numero_cliente,
                'complemento' => $request->complemento_cliente ?? '',
                'bairro'      => $request->bairro_cliente,
                'cidade'      => $request->cidade_cliente,
                'estado'      => $request->estado_cliente,
                'cep'         => $request->cep_cliente,
            ],
            metadata: $request->metadata,
        );
    }
}
