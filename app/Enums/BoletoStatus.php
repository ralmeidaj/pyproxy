<?php

namespace App\Enums;

enum BoletoStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case Expired   = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pendente',
            self::Paid      => 'Pago',
            self::Cancelled => 'Cancelado',
            self::Expired   => 'Expirado',
        };
    }

    public function canCancel(): bool
    {
        return $this === self::Pending;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Expired], true);
    }
}
