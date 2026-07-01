<?php

namespace App\Console\Commands;

use App\Jobs\DeliverWebhookJob;
use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

class RetryWebhooksCommand extends Command
{
    protected $signature = 'webhooks:retry';

    protected $description = 'Reprocessa webhook deliveries falhados com next_attempt_at vencido (RF-27)';

    public function handle(): int
    {
        $deliveries = WebhookDelivery::where('status', 'failed')
            ->where('attempts', '<', 3)
            ->whereNotNull('next_attempt_at')
            ->where('next_attempt_at', '<=', now())
            ->get();

        if ($deliveries->isEmpty()) {
            return Command::SUCCESS;
        }

        foreach ($deliveries as $delivery) {
            DeliverWebhookJob::dispatch($delivery->id);
        }

        $this->info("Webhooks reprocessados: {$deliveries->count()}");
        return Command::SUCCESS;
    }
}
