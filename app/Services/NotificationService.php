<?php

namespace App\Services;

use App\Enums\CommunicationModel;
use App\Enums\NotificationEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Boleto;
use App\Models\NotificationLog;

class NotificationService
{
    public function notify(Boleto $boleto, NotificationEvent $event): void
    {
        $boleto->loadMissing('tenant');

        if (! $boleto->payer_email) {
            return;
        }

        $emailLog = $this->createLog($boleto, $event, 'email', $boleto->payer_email);
        SendEmailNotificationJob::dispatch($boleto->id, $event->value, $emailLog->id);

        if (
            $boleto->tenant->communication_model === CommunicationModel::EmailWhatsApp
            && $boleto->payer_phone
        ) {
            $waLog = $this->createLog($boleto, $event, 'whatsapp', $boleto->payer_phone);
            SendWhatsAppNotificationJob::dispatch($boleto->id, $event->value, $waLog->id);
        }
    }

    private function createLog(
        Boleto $boleto,
        NotificationEvent $event,
        string $channel,
        string $recipient,
    ): NotificationLog {
        return NotificationLog::create([
            'boleto_id' => $boleto->id,
            'tenant_id' => $boleto->tenant_id,
            'event'     => $event->value,
            'channel'   => $channel,
            'recipient' => $recipient,
            'status'    => 'queued',
        ]);
    }
}
