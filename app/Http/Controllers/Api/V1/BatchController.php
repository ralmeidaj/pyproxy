<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueBatchRequest;
use App\Jobs\ProcessBatchJob;
use App\Models\BoletosBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BatchController extends Controller
{
    #[OA\Post(
        path: '/boletos/batch',
        summary: 'Emitir lote de boletos',
        description: 'Cria um lote de até 500 boletos. O processamento é assíncrono: retorna HTTP 202 com o ID do lote para consulta posterior. A operação é idempotente por `external_ref` por tenant.',
        security: [['ApiKey' => []]],
        tags: ['Boletos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['external_ref', 'boletos'],
                properties: [
                    new OA\Property(property: 'external_ref', type: 'string', example: 'LOTE-IPTU-2026-07', description: 'Referência única do lote por tenant'),
                    new OA\Property(property: 'boletos', type: 'array', maxItems: 500, items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 202, description: 'Lote aceito para processamento'),
            new OA\Response(response: 401, description: 'API Key inválida ou ausente'),
            new OA\Response(response: 409, description: 'external_ref já existe para este tenant'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(IssueBatchRequest $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->hasScope('boleto:write')) {
            abort(403, 'Esta API key não possui escopo boleto:write.');
        }

        if (! $apiKey->allow_batch) {
            abort(403, 'Esta API key não possui permissão para emissão em lote.');
        }

        // Idempotência por external_ref + tenant
        $existing = BoletosBatch::where('tenant_id', $tenant->id)
            ->where('external_ref', $request->external_ref)
            ->first();

        if ($existing) {
            return $this->batchResponse($existing, 200);
        }

        $batch = BoletosBatch::create([
            'tenant_id'   => $tenant->id,
            'api_key_id'  => $apiKey->id,
            'external_ref' => $request->external_ref,
            'status'      => 'pending',
            'total_count' => count($request->boletos),
            'items'       => $request->boletos,
        ]);

        ProcessBatchJob::dispatch($batch->id);

        return $this->batchResponse($batch, 202);
    }

    #[OA\Get(
        path: '/boletos/batch/{batch_id}',
        summary: 'Consultar status do lote',
        description: 'Retorna o status de processamento do lote e os resultados individuais de cada boleto.',
        security: [['ApiKey' => []]],
        tags: ['Boletos'],
        parameters: [
            new OA\Parameter(name: 'batch_id', in: 'path', required: true, description: 'ID do lote', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status do lote'),
            new OA\Response(response: 404, description: 'Lote não encontrado'),
        ]
    )]
    public function show(Request $request, BoletosBatch $batch): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey->hasScope('boleto:read')) {
            abort(403, 'Esta API key não possui escopo boleto:read.');
        }

        if ($batch->tenant_id !== $tenant->id) {
            abort(404);
        }

        return $this->batchResponse($batch, 200);
    }

    private function batchResponse(BoletosBatch $batch, int $status): JsonResponse
    {
        return response()->json([
            'id'              => $batch->id,
            'external_ref'    => $batch->external_ref,
            'status'          => $batch->status->value,
            'status_label'    => $batch->status->label(),
            'total_count'     => $batch->total_count,
            'processed_count' => $batch->processed_count,
            'success_count'   => $batch->success_count,
            'error_count'     => $batch->error_count,
            'started_at'      => $batch->started_at?->toIso8601String(),
            'finished_at'     => $batch->finished_at?->toIso8601String(),
            'created_at'      => $batch->created_at->toIso8601String(),
            'results'         => $batch->results,
        ], $status);
    }
}
