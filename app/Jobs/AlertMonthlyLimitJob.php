<?php

namespace App\Jobs;

use App\Mail\MonthlyLimitAlertMail;
use App\Models\ApiKey;
use App\Models\BackofficeUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertMonthlyLimitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $apiKeyId,
        private readonly int $currentCount,
    ) {}

    public function handle(): void
    {
        $apiKey = ApiKey::with('tenant')->find($this->apiKeyId);

        if (! $apiKey || ! $apiKey->monthly_limit) {
            return;
        }

        $percentUsed = (int) round(($this->currentCount / $apiKey->monthly_limit) * 100);

        $admins = BackofficeUser::whereIn('role', ['super_admin', 'admin'])->get();

        if ($admins->isEmpty()) {
            Log::warning('AlertMonthlyLimitJob: nenhum admin encontrado para enviar alerta', [
                'api_key_id' => $this->apiKeyId,
            ]);
            return;
        }

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(
                new MonthlyLimitAlertMail($apiKey, $this->currentCount, $apiKey->monthly_limit, $percentUsed)
            );
        }

        Log::info('AlertMonthlyLimitJob: alerta de 80% enviado', [
            'api_key_id'    => $apiKey->id,
            'api_key_name'  => $apiKey->name,
            'tenant'        => $apiKey->tenant->name,
            'current_count' => $this->currentCount,
            'monthly_limit' => $apiKey->monthly_limit,
            'percent_used'  => $percentUsed,
            'admins_count'  => $admins->count(),
        ]);
    }
}
