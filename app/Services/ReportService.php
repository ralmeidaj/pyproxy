<?php

namespace App\Services;

use App\Jobs\ExportReportJob;
use App\Models\Boleto;
use App\Models\ReportExport;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    // ─── RF-44: Sumário ───────────────────────────────────────────────────────

    public function summary(?Tenant $tenant, string $from, string $to): array
    {
        $q = $this->baseQuery($tenant)
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        $total     = $q->clone()->count();
        $paid      = $q->clone()->where('status', 'paid')->count();
        $cancelled = $q->clone()->where('status', 'cancelled')->count();
        $expired   = $q->clone()->where('status', 'expired')->count();

        $amountIssued = (int) $q->clone()->sum('amount_cents');
        $amountPaid   = (int) $q->clone()->where('status', 'paid')->sum('paid_amount_cents');

        return [
            'total_issued'        => $total,
            'total_paid'          => $paid,
            'total_cancelled'     => $cancelled,
            'total_expired'       => $expired,
            'amount_issued_cents' => $amountIssued,
            'amount_paid_cents'   => $amountPaid,
            'liquidation_rate'    => $total > 0 ? round($paid / $total * 100, 2) : 0,
            'avg_ticket_cents'    => $total > 0 ? (int) round($amountIssued / $total) : 0,
        ];
    }

    // ─── RF-46: Por canal de pagamento ───────────────────────────────────────

    public function byChannel(?Tenant $tenant, string $from, string $to): array
    {
        return $this->baseQuery($tenant)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->selectRaw('paid_channel, count(*) as count, sum(paid_amount_cents) as amount_cents')
            ->groupBy('paid_channel')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'channel'      => $r->paid_channel ?? 'Não informado',
                'count'        => (int) $r->count,
                'amount_cents' => (int) $r->amount_cents,
            ])
            ->toArray();
    }

    // ─── RF-48: Inadimplência ────────────────────────────────────────────────

    public function delinquency(?Tenant $tenant): array
    {
        $base = $this->baseQuery($tenant)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString());

        $total   = $base->clone()->count();
        $over30  = $base->clone()->whereDate('due_date', '<=', now()->subDays(30)->toDateString())->count();
        $over60  = $base->clone()->whereDate('due_date', '<=', now()->subDays(60)->toDateString())->count();
        $over90  = $base->clone()->whereDate('due_date', '<=', now()->subDays(90)->toDateString())->count();
        $amount  = (int) $base->clone()->sum('amount_cents');

        return [
            'total_overdue'        => $total,
            'over_30_days'         => $over30,
            'over_60_days'         => $over60,
            'over_90_days'         => $over90,
            'total_overdue_cents'  => $amount,
        ];
    }

    // ─── RF-49/50: Exportação assíncrona ─────────────────────────────────────

    public function export(
        ?Tenant $tenant,
        array   $filters,
        string  $format,
        int     $requestedById,
        string  $requestedByType,
    ): ReportExport {
        $export = ReportExport::create([
            'tenant_id'         => $tenant?->id,
            'requested_by_type' => $requestedByType,
            'requested_by_id'   => $requestedById,
            'format'            => $format,
            'filters'           => $filters,
            'status'            => 'pending',
        ]);

        ExportReportJob::dispatch($export->id);

        return $export;
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function baseQuery(?Tenant $tenant): Builder
    {
        return Boleto::query()->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id));
    }
}
