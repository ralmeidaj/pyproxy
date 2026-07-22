<?php

namespace App\Http\Controllers\Backoffice;

use App\DTOs\CreateTenantData;
use App\DTOs\UpdateTenantStatusData;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateTenantRequest;
use App\Http\Requests\Backoffice\UpdateTenantRequest;
use App\Http\Requests\Backoffice\UpdateTenantStatusRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    public function index(Request $request): Response
    {
        $tenants = $this->tenantService->paginate(
            perPage: 20,
            search:  $request->input('search'),
            status:  $request->filled('status') ? TenantStatus::from($request->input('status')) : null,
        );

        return Inertia::render('Backoffice/Tenants/Index', [
            'tenants'  => $tenants,
            'filters'  => $request->only('search', 'status'),
            'statuses' => collect(TenantStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Backoffice/Tenants/Create');
    }

    public function store(CreateTenantRequest $request): RedirectResponse
    {
        $tenant = $this->tenantService->create(CreateTenantData::fromRequest($request));

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', 'Tenant cadastrado com sucesso.');
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load([
            'statusHistory.backofficeUser',
            'apiKeys'        => fn ($q) => $q->latest(),
            'boletoConfigs'  => fn ($q) => $q->select('id', 'tenant_id', 'name', 'is_default', 'status')->latest(),
            'arDigitalConfig',
        ]);

        return Inertia::render('Backoffice/Tenants/Show', [
            'tenant' => array_merge($tenant->toArray(), [
                'status_label' => $tenant->status->label(),
                'allowed_transitions' => collect($tenant->status->allowedTransitions())
                    ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            ]),
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Backoffice/Tenants/Edit', [
            'tenant' => $tenant->only('id', 'name', 'document', 'email', 'phone', 'communication_model', 'notes', 'allowed_ips', 'email_entity_name', 'email_logo_url', 'email_custom_text'),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $document = app(\App\Services\SanitizationService::class)->validateDocument($request->document);

        if (! $document) {
            return back()->withErrors(['document' => 'CNPJ inválido.'])->withInput();
        }

        $allowedIps = $request->filled('allowed_ips')
            ? array_values(array_filter(array_map('trim', explode(',', $request->allowed_ips))))
            : null;

        $tenant->update([
            'name'                => $request->name,
            'document'            => $document,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'communication_model' => $request->communication_model,
            'notes'               => $request->notes,
            'allowed_ips'         => $allowedIps ?: null,
            'email_entity_name'   => $request->email_entity_name ?: null,
            'email_logo_url'      => $request->email_logo_url ?: null,
            'email_custom_text'   => $request->email_custom_text ?: null,
        ]);

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', 'Tenant atualizado com sucesso.');
    }

    public function updateStatus(UpdateTenantStatusRequest $request, Tenant $tenant): RedirectResponse
    {
        $actor = Auth::guard('backoffice')->user();

        $this->tenantService->updateStatus(
            $tenant,
            UpdateTenantStatusData::fromRequest($request, $actor),
        );

        return back()->with('success', 'Status atualizado com sucesso.');
    }

    public function approve(Request $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->status !== TenantStatus::PendingApproval) {
            return back()->with('error', 'Tenant não está pendente de aprovação.');
        }

        $actor = Auth::guard('backoffice')->user();

        $this->tenantService->updateStatus($tenant, new UpdateTenantStatusData(
            newStatus: TenantStatus::Active,
            reason:    'Aprovado via backoffice.',
            actor:     $actor,
            ip:        $request->ip(),
        ));

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', "Tenant \"{$tenant->name}\" aprovado com sucesso.");
    }
}
