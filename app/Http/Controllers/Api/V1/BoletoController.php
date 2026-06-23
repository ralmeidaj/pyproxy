<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\IssueBoletoData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueBoletoRequest;
use App\Http\Resources\BoletoResource;
use App\Models\Boleto;
use App\Services\BoletoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoletoController extends Controller
{
    public function __construct(private readonly BoletoService $boletoService) {}

    public function store(IssueBoletoRequest $request): BoletoResource
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        // Verifica escopo (RNF-13)
        if (! $apiKey->hasScope('boleto:write')) {
            abort(403, 'Esta API key não possui escopo boleto:write.');
        }

        $boleto = $this->boletoService->issue(
            $tenant,
            IssueBoletoData::fromRequest($request),
        );

        return new BoletoResource($boleto->load('splits'));
    }

    public function show(Request $request, Boleto $boleto): BoletoResource
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->hasScope('boleto:read')) {
            abort(403, 'Esta API key não possui escopo boleto:read.');
        }

        if ($boleto->tenant_id !== $tenant->id) {
            abort(404);
        }

        return new BoletoResource($boleto->load('splits'));
    }

    public function destroy(Request $request, Boleto $boleto): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->hasScope('boleto:write')) {
            abort(403, 'Esta API key não possui escopo boleto:write.');
        }

        if ($boleto->tenant_id !== $tenant->id) {
            abort(404);
        }

        $this->boletoService->cancel($boleto, $tenant);

        return response()->json(['message' => 'Boleto cancelado com sucesso.']);
    }
}
