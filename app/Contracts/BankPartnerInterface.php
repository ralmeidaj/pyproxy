<?php

namespace App\Contracts;

use App\DTOs\BankBoletoResult;
use App\DTOs\IssueBoletoData;
use App\Models\BoletoConfig;

interface BankPartnerInterface
{
    public function issueBoleto(IssueBoletoData $data, BoletoConfig $config, array $splits = []): BankBoletoResult;

    public function cancelBoleto(string $bankBoletoId, BoletoConfig $config): void;

    public function getBoletoStatus(string $bankBoletoId, BoletoConfig $config): array;
}
