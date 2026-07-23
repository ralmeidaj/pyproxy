<?php

namespace App\Mail;

use App\Models\ContribuinteAccessToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContribuinteAccessLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ContribuinteAccessToken $accessToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Acesso ao Portal do Contribuinte — Payproxy');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contribuinte.access-link',
            with: [
                'debitosUrl' => route('contribuinte.debitos', $this->accessToken->token),
                'dadosUrl'   => route('contribuinte.meus-dados', $this->accessToken->token),
                'expiresAt'  => $this->accessToken->expires_at->format('d/m/Y \à\s H:i'),
            ],
        );
    }
}
