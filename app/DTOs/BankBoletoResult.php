<?php

namespace App\DTOs;

// Resultado normalizado da emissão de boleto no parceiro bancário (RF-PART-08)
final readonly class BankBoletoResult
{
    public function __construct(
        public string  $bankBoletoId,
        public string  $barcode,
        public string  $digitableLine,
        public ?string $pixQrCode,
        public ?string $pdfUrl,
        public bool    $ddaRegistered,
        public ?string $tokenFacilitador = null,
    ) {}
}
