<?php

namespace App\Http\Controllers\Backoffice;

use App\DTOs\CreateApiKeyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateApiKeyRequest;
use App\Http\Requests\Backoffice\UpdateApiKeyRequest;
use App\Models\ApiKey;
use App\Models\Tenant;
use App\Services\ApiKeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeyService) {}

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Backoffice/Tenants/ApiKeys/Create', [
            'tenant' => $tenant->only('id', 'name'),
        ]);
    }

    public function store(CreateApiKeyRequest $request, Tenant $tenant): RedirectResponse
    {
        ['api_key' => $apiKey, 'plain_key' => $plainKey] = $this->apiKeyService->generate(
            $tenant,
            CreateApiKeyData::fromRequest($request),
        );

        // Plain key shown only once via flash — never stored
        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('api_key_created', [
                'id'        => $apiKey->id,
                'name'      => $apiKey->name,
                'plain_key' => $plainKey,
            ]);
    }

    public function edit(Tenant $tenant, ApiKey $apiKey): Response|RedirectResponse
    {
        if ($apiKey->tenant_id !== $tenant->id) {
            abort(404);
        }

        if (! $apiKey->isActive()) {
            return redirect()->route('backoffice.tenants.show', $tenant)
                ->with('error', 'Não é possível editar uma API key revogada ou expirada.');
        }

        return Inertia::render('Backoffice/Tenants/ApiKeys/Edit', [
            'tenant' => $tenant->only('id', 'name'),
            'apiKey' => [
                'id'                     => $apiKey->id,
                'name'                   => $apiKey->name,
                'key_prefix'             => $apiKey->key_prefix,
                'scopes'                 => $apiKey->scopes ?? [],
                'rate_limit_per_minute'  => $apiKey->rate_limit_per_minute,
                'daily_limit'            => $apiKey->daily_limit,
                'monthly_limit'          => $apiKey->monthly_limit,
                'max_amount_cents'       => $apiKey->max_amount_cents,
                'allow_batch'            => $apiKey->allow_batch,
                'allowed_metadata_types' => $apiKey->allowed_metadata_types ?? [],
                'expires_at'             => $apiKey->expires_at?->toDateString(),
            ],
        ]);
    }

    public function update(UpdateApiKeyRequest $request, Tenant $tenant, ApiKey $apiKey): RedirectResponse
    {
        if ($apiKey->tenant_id !== $tenant->id) {
            abort(404);
        }

        if (! $apiKey->isActive()) {
            return redirect()->route('backoffice.tenants.show', $tenant)
                ->with('error', 'Não é possível editar uma API key revogada ou expirada.');
        }

        $apiKey->update([
            'name'                   => $request->name,
            'scopes'                 => $request->scopes,
            'rate_limit_per_minute'  => $request->rate_limit_per_minute,
            'daily_limit'            => $request->daily_limit,
            'monthly_limit'          => $request->monthly_limit,
            'max_amount_cents'       => $request->max_amount_cents,
            'allow_batch'            => $request->boolean('allow_batch'),
            'allowed_metadata_types' => $request->allowed_metadata_types ?: null,
            'expires_at'             => $request->expires_at ?: null,
        ]);

        return redirect()->route('backoffice.tenants.show', $tenant)
            ->with('success', "API key \"{$apiKey->name}\" atualizada com sucesso.");
    }

    public function revoke(Tenant $tenant, ApiKey $apiKey): RedirectResponse
    {
        $actor = Auth::guard('backoffice')->user();

        $this->apiKeyService->revoke($apiKey, $actor, request()->ip());

        return back()->with('success', 'API key revogada com sucesso.');
    }
}
