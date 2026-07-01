<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantUserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TenantUser $tenantUser,
        public readonly string     $rawToken,
        public readonly Tenant     $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Convite para acessar o portal {$this->tenant->name} — Payproxy",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant-user-invite');
    }
}
