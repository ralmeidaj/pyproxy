<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Backoffice/Dashboard', [
            'user'  => Auth::guard('backoffice')->user()->only('name', 'email', 'role'),
            'stats' => [
                'total_tenants'      => Tenant::count(),
                'active_tenants'     => Tenant::where('status', 'active')->count(),
                'pending_tenants'    => Tenant::where('status', 'pending_approval')->count(),
                'boletos_this_month' => Boleto::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'boletos_paid_month' => Boleto::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', 'paid')
                    ->count(),
            ],
        ]);
    }
}
