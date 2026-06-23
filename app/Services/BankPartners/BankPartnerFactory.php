<?php

namespace App\Services\BankPartners;

use App\Contracts\BankPartnerInterface;
use App\Exceptions\BankPartnerException;
use App\Models\BankPartner;

class BankPartnerFactory
{
    public function make(BankPartner $partner): BankPartnerInterface
    {
        return match($partner->slug) {
            'pjbank' => app(PJBankService::class),
            default  => throw new BankPartnerException("Parceiro bancário '{$partner->slug}' não possui adapter implementado."),
        };
    }
}
