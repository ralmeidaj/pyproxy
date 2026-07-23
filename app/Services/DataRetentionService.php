<?php

namespace App\Services;

use App\Models\ArDigitalEvent;
use App\Models\AuditLog;
use App\Models\Boleto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionService
{
    public function anonymizeBoletos(bool $dryRun = false): int
    {
        $cutoff = now()->subYears(5);

        $query = Boleto::withTrashed()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('payer_name');

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update([
                'payer_name'     => null,
                'payer_document' => null,
                'payer_email'    => null,
                'payer_phone'    => null,
                'payer_address'  => null,
            ]);

            Log::info('[DataRetention] Boletos anonimizados', ['count' => $count, 'cutoff' => $cutoff->toDateString()]);
        }

        return $count;
    }

    public function anonymizeArEvents(bool $dryRun = false): int
    {
        // Mantém timestamps e carimbos; apaga dados pessoais dos eventos AR
        $cutoff = now()->subYears(5);

        $count = DB::table('ar_digital_notifications')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('destinatario_email')
            ->count();

        if (! $dryRun && $count > 0) {
            DB::table('ar_digital_notifications')
                ->where('created_at', '<', $cutoff)
                ->whereNotNull('destinatario_email')
                ->update([
                    'destinatario_email'    => null,
                    'destinatario_whatsapp' => null,
                ]);

            Log::info('[DataRetention] Notificações AR anonimizadas', ['count' => $count]);
        }

        return $count;
    }

    public function purgeAuditLogs(bool $dryRun = false): int
    {
        $cutoff = now()->subYears(2);

        $count = AuditLog::where('created_at', '<', $cutoff)->count();

        if (! $dryRun && $count > 0) {
            AuditLog::where('created_at', '<', $cutoff)->delete();
            Log::info('[DataRetention] AuditLogs excluídos', ['count' => $count]);
        }

        return $count;
    }

    public function purgeNotificationLogs(bool $dryRun = false): int
    {
        $cutoff = now()->subYears(5);

        $count = DB::table('notification_logs')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('recipient')
            ->count();

        if (! $dryRun && $count > 0) {
            DB::table('notification_logs')
                ->where('created_at', '<', $cutoff)
                ->whereNotNull('recipient')
                ->update(['recipient' => null]);

            Log::info('[DataRetention] notification_logs anonimizados', ['count' => $count]);
        }

        return $count;
    }

    public function purgeRevokedApiKeys(bool $dryRun = false): int
    {
        $cutoff = now()->subDays(90);

        $count = DB::table('api_keys')
            ->whereNotNull('revoked_at')
            ->where('revoked_at', '<', $cutoff)
            ->count();

        if (! $dryRun && $count > 0) {
            DB::table('api_keys')
                ->whereNotNull('revoked_at')
                ->where('revoked_at', '<', $cutoff)
                ->delete();

            Log::info('[DataRetention] API Keys revogadas excluídas', ['count' => $count]);
        }

        return $count;
    }
}
