<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StoreSplitConfigRequest;
use App\Models\BoletoConfig;
use App\Models\SplitConfig;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class SplitConfigController extends Controller
{
    public function store(StoreSplitConfigRequest $request, Tenant $tenant, BoletoConfig $boletoConfig): RedirectResponse
    {
        $boletoConfig->splitConfigs()->create([
            'name'          => $request->name,
            'type'          => $request->type,
            'value'         => $request->value,
            'priority'      => $request->integer('priority', 0),
            'payee_details' => $request->payee_details,
        ]);

        return redirect()
            ->route('backoffice.tenants.boleto-configs.edit', [$tenant, $boletoConfig])
            ->with('success', 'Favorecido de split adicionado.');
    }

    public function destroy(Tenant $tenant, BoletoConfig $boletoConfig, SplitConfig $splitConfig): RedirectResponse
    {
        $splitConfig->delete();

        return redirect()
            ->route('backoffice.tenants.boleto-configs.edit', [$tenant, $boletoConfig])
            ->with('success', 'Favorecido de split removido.');
    }
}
