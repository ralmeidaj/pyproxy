<?php

namespace App\DTOs;

use App\Http\Requests\Backoffice\CreateBoletoConfigRequest;

final readonly class CreateBoletoConfigData
{
    public function __construct(
        public int     $bankPartnerId,
        public string  $name,
        public bool    $isDefault,
        public string  $credentialApiKey,
        public string  $credentialChave,
        public int     $prazoVencimentoDias,
        public float   $multaPercentual,
        public float   $jurosPercentualMes,
        public ?float  $descontoPercentual,
        public ?int    $descontoAntecedenciaDias,
        public array   $instrucoes,
        public ?string $webhookUrl,
        public ?string $webhookSecret,
    ) {}

    public static function fromRequest(CreateBoletoConfigRequest $request): self
    {
        return new self(
            bankPartnerId:            $request->bank_partner_id,
            name:                     $request->name,
            isDefault:                (bool) $request->is_default,
            credentialApiKey:         $request->credential_api_key,
            credentialChave:          $request->credential_chave,
            prazoVencimentoDias:      $request->prazo_vencimento_dias ?? 30,
            multaPercentual:          (float) ($request->multa_percentual ?? 0),
            jurosPercentualMes:       (float) ($request->juros_percentual_mes ?? 0),
            descontoPercentual:       $request->desconto_percentual ? (float) $request->desconto_percentual : null,
            descontoAntecedenciaDias: $request->desconto_antecedencia_dias,
            instrucoes:               $request->instrucoes ?? [],
            webhookUrl:               $request->webhook_url,
            webhookSecret:            $request->webhook_secret,
        );
    }
}
