<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Models\Tenant;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(): Response
    {
        $month = now()->month;
        $year  = now()->year;
        $from  = now()->subDays(6)->toDateString();
        $to    = now()->toDateString();

        $statusRows = Boleto::whereMonth('created_at', $month)
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

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->toDateString();

        return Inertia::render('Backoffice/Dashboard', [
            'user'  => Auth::guard('backoffice')->user()->only('name', 'email', 'role'),
            'stats' => [
                'total_tenants'      => Tenant::count(),
                'active_tenants'     => Tenant::where('status', 'active')->count(),
                'pending_tenants'    => Tenant::where('status', 'pending_approval')->count(),
                'boletos_this_month' => Boleto::whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
                'boletos_paid_month' => Boleto::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('status', 'paid')->count(),
            ],
            'chartSeries'     => $this->reports->timeSeries(null, $from, $to, 'daily'),
            'statusBreakdown' => $statusBreakdown,
            'tributeBreakdown' => $this->reports->byMetadata(null, $monthStart, $monthEnd, 'tipo'),
        ]);
    }
}
