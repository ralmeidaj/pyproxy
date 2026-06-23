<?php

namespace App\Http\Controllers\Backoffice;

use App\DTOs\CreateBoletoConfigData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateBoletoConfigRequest;
use App\Models\BankPartner;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\BoletoConfigService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BoletoConfigController extends Controller
{
    public function __construct(private readonly BoletoConfigService $configService) {}

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Backoffice/BoletoConfigs/Create', [
            'tenant'        => $tenant->only('id', 'name'),
            'bankPartners'  => BankPartner::where('status', 'active')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(CreateBoletoConfigRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->configService->create($tenant, CreateBoletoConfigData::fromRequest($request));

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', 'Configuração de boleto criada com sucesso.');
    }

    public function edit(Tenant $tenant, BoletoConfig $boletoConfig): Response
    {
        return Inertia::render('Backoffice/BoletoConfigs/Edit', [
            'tenant'       => $tenant->only('id', 'name'),
            'config'       => array_merge($boletoConfig->only(
                'id', 'bank_partner_id', 'name', 'is_default',
                'prazo_vencimento_dias', 'multa_percentual', 'juros_percentual_mes',
                'desconto_percentual', 'desconto_antecedencia_dias',
                'instrucoes', 'webhook_url', 'status',
            ), ['has_webhook_secret' => (bool) $boletoConfig->webhook_secret_encrypted]),
            'bankPartners' => BankPartner::where('status', 'active')->get(['id', 'name', 'slug']),
            'splits'       => $boletoConfig->splitConfigs()->get([
                'id', 'name', 'bank_partner_payee_id', 'payee_details', 'type', 'value', 'priority',
            ]),
        ]);
    }

    public function update(CreateBoletoConfigRequest $request, Tenant $tenant, BoletoConfig $boletoConfig): RedirectResponse
    {
        $this->configService->update($boletoConfig, CreateBoletoConfigData::fromRequest($request));

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', 'Configuração de boleto atualizada com sucesso.');
    }
}
