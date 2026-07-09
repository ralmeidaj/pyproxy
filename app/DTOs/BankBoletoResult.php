<?php

namespace App\DTOs;

final readonly class BankBoletoResult
{
    public function __construct(
        public string  $bankBoletoId,
        public string  $barcode,
        public string  $digitableLine,
        public ?string $pixQrCode,
        public ?string $pdfUrl,
        public bool    $ddaRegistered,
        public ?string $tokenFacilitador   = null,
        public array   $requestPayload     = [],
        public array   $responsePayload    = [],
    ) {}
}