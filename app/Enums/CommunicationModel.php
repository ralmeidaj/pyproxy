<?php

namespace App\Enums;

enum CommunicationModel: string
{
    case Email         = 'email';
    case EmailWhatsApp = 'email_whatsapp';

    public function label(): string
    {
        return match($this) {
            self::Email         => 'Modelo 1 — E-mail',
            self::EmailWhatsApp => 'Modelo 2 — E-mail + WhatsApp',
        };
    }
}
