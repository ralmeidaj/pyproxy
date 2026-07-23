<?php

namespace App\Mail;

use App\Enums\NotificationEvent;
use App\Models\ArDigitalNotification;
use App\Models\Boleto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BoletoNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Boleto $boleto,
        public readonly NotificationEvent $event,
        public readonly ?ArDigitalNotification $arNotification = null,
        public readonly bool $pixelTracking = false,
        public readonly ?string $whatsappOptInUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->event->subject());
    }

    public function content(): Content
    {
        return new Content(view: $this->event->mailView());
    }
}
