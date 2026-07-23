<?php

namespace App\Mail;

use App\Models\ApiKey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiKeyExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ApiKey $apiKey,
        public readonly int    $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "API Key \"{$this->apiKey->name}\" expira em {$this->daysLeft} dias",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.api-key-expiring');
    }
}
