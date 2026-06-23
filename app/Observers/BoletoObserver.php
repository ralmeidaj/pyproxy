<?php

namespace App\Observers;

use App\Enums\BoletoStatus;
use App\Enums\NotificationEvent;
use App\Models\Boleto;
use App\Services\NotificationService;

class BoletoObserver
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function created(Boleto $boleto): void
    {
        $this->notifications->notify($boleto, NotificationEvent::Issued);
    }

    public function updated(Boleto $boleto): void
    {
        if (! $boleto->wasChanged('status')) {
            return;
        }

        $event = match($boleto->status) {
            BoletoStatus::Paid      => NotificationEvent::Paid,
            BoletoStatus::Cancelled => NotificationEvent::Cancelled,
            default                 => null,
        };

        if ($event !== null) {
            $this->notifications->notify($boleto, $event);
        }
    }
}
