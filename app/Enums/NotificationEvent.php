<?php

namespace App\Enums;

enum NotificationEvent: string
{
    case Issued    = 'issued';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case DueSoon   = 'due_soon';
    case Overdue   = 'overdue';

    public function subject(): string
    {
        return match($this) {
            self::Issued    => 'Seu boleto foi emitido',
            self::Paid      => 'Pagamento confirmado — obrigado!',
            self::Cancelled => 'Boleto cancelado',
            self::DueSoon   => 'Seu boleto vence em 2 dias',
            self::Overdue   => 'Boleto vencido',
        };
    }

    public function mailView(): string
    {
        return match($this) {
            self::Issued    => 'mail.boleto.issued',
            self::Paid      => 'mail.boleto.paid',
            self::Cancelled => 'mail.boleto.cancelled',
            self::DueSoon   => 'mail.boleto.due_soon',
            self::Overdue   => 'mail.boleto.overdue',
        };
    }

    public function whatsAppTemplate(): string
    {
        return match($this) {
            self::Issued    => 'boleto_issued',
            self::Paid      => 'boleto_paid',
            self::Cancelled => 'boleto_cancelled',
            self::DueSoon   => 'boleto_due_soon',
            self::Overdue   => 'boleto_overdue',
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Issued    => 'Emissão',
            self::Paid      => 'Pagamento',
            self::Cancelled => 'Cancelamento',
            self::DueSoon   => 'Vencimento próximo',
            self::Overdue   => 'Vencido',
        };
    }
}
