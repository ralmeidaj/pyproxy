<?php

namespace App\Jobs;

use App\Mail\ApiKeyExpiringMail;
use App\Models\ApiKey;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyExpiringApiKeysJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        foreach ([15, 7] as $days) {
            ApiKey::with('tenant')
                ->whereNull('revoked_at')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [
                    now()->addDays($days)->startOfDay(),
                    now()->addDays($days)->endOfDay(),
                ])
                ->get()
                ->each(function (ApiKey $key) use ($days) {
                    if (! $key->tenant?->email) {
                        return;
                    }

                    Mail::to($key->tenant->email)->send(new ApiKeyExpiringMail($key, $days));

                    Log::info('[ApiKey] Notificação de expiração enviada', [
                        'api_key_id' => $key->id,
                        'tenant_id'  => $key->tenant_id,
                        'days_left'  => $days,
                    ]);
                });
        }
    }
}
