<?php

namespace App\Http\Controllers\Backoffice;

use App\DTOs\CreateApiKeyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CreateApiKeyRequest;
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

    public function revoke(Tenant $tenant, ApiKey $apiKey): RedirectResponse
    {
        $actor = Auth::guard('backoffice')->user();

        $this->apiKeyService->revoke($apiKey, $actor, request()->ip());

        return back()->with('success', 'API key revogada com sucesso.');
    }
}
