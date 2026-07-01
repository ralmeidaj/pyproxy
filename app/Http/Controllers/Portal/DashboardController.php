<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BoletoStatus;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(): Response
    {
        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        $month = now()->month;
        $year  = now()->year;
        $from  = now()->subDays(6)->toDateString();
        $to    = now()->toDateString();

        $boletosQuery = $tenant->boletos();

        $thisMonth = $boletosQuery->clone()
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $stats = [
            'boletos_this_month' => $thisMonth->clone()->count(),
            'boletos_paid'       => $thisMonth->clone()->where('status', BoletoStatus::Paid)->count(),
            'boletos_pending'    => $thisMonth->clone()->where('status', BoletoStatus::Pending)->count(),
            'total_amount_cents' => (int) $thisMonth->clone()->sum('amount_cents'),
            'paid_amount_cents'  => (int) $thisMonth->clone()->where('status', BoletoStatus::Paid)->sum('paid_amount_cents'),
        ];

        $statusRows = $tenant->boletos()
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $statusBreakdown = ['pending' => 0, 'paid' => 0, 'cancelled' => 0, 'expired' => 0];
        foreach ($statusRows as $row) {
            $key = is_string($row->status) ? $row->status : $row->status->value;
            if (array_key_exists($key, $statusBreakdown)) {
                $statusBreakdown[$key] = (int) $row->total;
            }
        }

        $recentBoletos = $tenant->boletos()
            ->with('boletoConfig:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'external_ref', 'status', 'amount_cents', 'due_date', 'payer_name', 'boleto_config_id', 'created_at'])
            ->map(fn ($b) => array_merge($b->toArray(), [
                'status_label' => $b->status->label(),
            ]));

        return Inertia::render('Portal/Dashboard', [
            'tenant'         => $tenant->only('id', 'name'),
            'user'           => $user->only('id', 'name', 'email', 'role'),
            'stats'          => $stats,
            'recentBoletos'  => $recentBoletos,
            'chartSeries'    => $this->reports->timeSeries($tenant, $from, $to, 'daily'),
            'statusBreakdown' => $statusBreakdown,
        ]);
    }
}
