<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ArDigitalConfig;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArDigitalConfigController extends Controller
{
    public function show(Tenant $tenant): Response
    {
        $config = ArDigitalConfig::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'enabled'          => false,
                'pixel_tracking'   => false,
                'cpf_confirmation' => false,
                'act_provider'     => 'serpro',
            ]
        );

        return Inertia::render('Backoffice/ArDigital/Config', [
            'tenant' => $tenant->only('id', 'name'),
            'config' => $config->only('id', 'enabled', 'pixel_tracking', 'cpf_confirmation', 'act_provider'),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'          => ['required', 'boolean'],
            'pixel_tracking'   => ['required', 'boolean'],
            'cpf_confirmation' => ['required', 'boolean'],
            'act_provider'     => ['required', 'in:serpro,bry,soluti,certisign'],
        ]);

        ArDigitalConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            $validated
        );

        return back()->with('success', 'Configuração AR Digital atualizada com sucesso.');
    }
}
