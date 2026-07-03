<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::query()->orderByDesc('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        if ($request->filled('actor')) {
            $query->where('actor_label', 'like', '%' . $request->input('actor') . '%');
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from') . ' 00:00:00');
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Backoffice/AuditLogs/Index', [
            'logs'    => $logs,
            'filters' => $request->only('tenant_id', 'action', 'actor', 'from', 'to'),
            'tenants' => Tenant::orderBy('name')->get()->map(fn ($t) => [
                'id'   => $t->id,
                'name' => $t->name,
            ]),
        ]);
    }
}
