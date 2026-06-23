<?php

namespace App\Enums;

enum BankPartnerType: string
{
    case Fintech               = 'fintech';
    case Banco                 = 'banco';
    case CorrespondenteBancario = 'correspondente_bancario';

    public function label(): string
    {
        return match($this) {
            self::Fintech                => 'Fintech',
            self::Banco                  => 'Banco',
            self::CorrespondenteBancario => 'Correspondente Bancário',
        };
    }
}
