<?php

namespace App\Mail;

use App\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyLimitAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ApiKey $apiKey,
        public readonly int    $currentCount,
        public readonly int    $monthlyLimit,
        public readonly int    $percentUsed,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Payproxy] Alerta: API key atingiu {$this->percentUsed}% do limite mensal"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.monthly-limit-alert');
    }
}
