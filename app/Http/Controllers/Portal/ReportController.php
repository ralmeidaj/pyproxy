<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): Response
    {
        $tenant = Auth::guard('portal')->user()->tenant;

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $daysDiff    = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to));
        $granularity = $request->input('granularity', match(true) {
            $daysDiff <= 31 => 'daily',
            $daysDiff <= 90 => 'weekly',
            default         => 'monthly',
        });

        $metadataKey = $request->input('metadata_key');

        return Inertia::render('Portal/Reports/Index', [
            'summary'       => $this->reports->summary($tenant, $from, $to),
            'byChannel'     => $this->reports->byChannel($tenant, $from, $to),
            'delinquency'   => $this->reports->delinquency($tenant),
            'timeSeries'    => $this->reports->timeSeries($tenant, $from, $to, $granularity),
            'byMetadata'    => $metadataKey ? $this->reports->byMetadata($tenant, $from, $to, $metadataKey) : [],
            'recentExports' => ReportExport::where('tenant_id', $tenant->id)
                ->latest()
                ->limit(5)
                ->get(['id', 'format', 'status', 'row_count', 'download_url', 'expires_at', 'created_at']),
            'filters' => [
                'from'         => $from,
                'to'           => $to,
                'granularity'  => $granularity,
                'metadata_key' => $metadataKey,
            ],
        ]);
    }

    public function export(Request $request): RedirectResponse
    {
        $request->validate([
            'format' => ['required', 'in:csv,json'],
            'from'   => ['required', 'date'],
            'to'     => ['required', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:pending,paid,cancelled,expired'],
        ]);

        $user   = Auth::guard('portal')->user();
        $tenant = $user->tenant;

        $this->reports->export(
            tenant:          $tenant,
            filters:         $request->only('from', 'to', 'status'),
            format:          $request->format,
            requestedById:   $user->id,
            requestedByType: 'tenant_user',
        );

        return back()->with('success', 'Exportação iniciada. O arquivo ficará disponível em instantes.');
    }
}
