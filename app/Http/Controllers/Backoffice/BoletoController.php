<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\BoletoStatus;
use App\Http\Controllers\Controller;
use App\Models\Boleto;
use App\Models\Tenant;
use App\Services\BoletoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoletoController extends Controller
{
    public function __construct(private readonly BoletoService $boletoService) {}

    public function index(Request $request, Tenant $tenant): Response
    {
        $boletos = $this->boletoService->paginate(
            tenant:   $tenant,
            perPage:  20,
            status:   $request->input('status'),
            search:   $request->input('search'),
        );

        return Inertia::render('Backoffice/Boletos/Index', [
            'tenant'   => $tenant->only('id', 'name'),
            'boletos'  => $boletos,
            'filters'  => $request->only('status', 'search'),
            'statuses' => collect(BoletoStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function show(Tenant $tenant, Boleto $boleto): Response
    {
        abort_if($boleto->tenant_id !== $tenant->id, 404);

        $boleto->load('splits', 'bankPartner');

        $notificationLogs = $boleto->notificationLogs()
            ->orderByDesc('created_at')
            ->get(['id', 'event', 'channel', 'recipient', 'status', 'error', 'sent_at', 'created_at']);

        return Inertia::render('Backoffice/Boletos/Show', [
            'tenant' => $tenant->only('id', 'name'),
            'boleto' => array_merge($boleto->toArray(), [
                'status_label' => $boleto->status->label(),
                'can_cancel'   => $boleto->status->canCancel(),
            ]),
            'notificationLogs' => $notificationLogs,
        ]);
    }
}
