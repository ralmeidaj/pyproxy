<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\IssueBoletoData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssueBoletoRequest;
use App\Http\Resources\BoletoResource;
use App\Models\ApiKey;
use App\Models\ApiKeyUsageDaily;
use App\Models\Boleto;
use App\Services\BoletoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BoletoController extends Controller
{
    public function __construct(private readonly BoletoService $boletoService) {}

    #[OA\Post(
        path: '/boletos',
        summary: 'Emitir boleto',
        description: 'Emite um boleto registrado com split de pagamento automático conforme a configuração do tenant. A operação é idempotente: requisições com o mesmo `pedido_numero` retornam o boleto existente.',
        security: [['ApiKey' => []]],
        tags: ['Boletos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pedido_numero', 'valor', 'vencimento', 'nome_cliente', 'cpf_cliente', 'endereco_cliente', 'numero_cliente', 'bairro_cliente', 'cidade_cliente', 'estado_cliente', 'cep_cliente'],
                properties: [
                    new OA\Property(property: 'pedido_numero',    type: 'string',  example: 'NF-2024-001',       description: 'Referência externa única do tenant'),
                    new OA\Property(property: 'valor',            type: 'string',  example: '150.00',            description: 'Valor do boleto em reais (string com 2 casas decimais)'),
                    new OA\Property(property: 'vencimento',       type: 'string',  example: '07/30/2026',        description: 'Data de vencimento no formato MM/DD/YYYY'),
                    new OA\Property(property: 'nome_cliente',     type: 'string',  example: 'João da Silva',     description: 'Nome completo do pagador'),
                    new OA\Property(property: 'cpf_cliente',      type: 'string',  example: '123.456.789-09',    description: 'CPF ou CNPJ do pagador (formatado ou somente dígitos)'),
                    new OA\Property(property: 'email_cliente',    type: 'string',  example: 'joao@example.com',  description: 'E-mail do pagador (opcional)'),
                    new OA\Property(property: 'telefone_cliente', type: 'string',  example: '71999990000',       description: 'Telefone do pagador (opcional)'),
                    new OA\Property(property: 'endereco_cliente', type: 'string',  example: 'Rua das Flores',    description: 'Logradouro do pagador'),
                    new OA\Property(property: 'numero_cliente',   type: 'string',  example: '42',                description: 'Número do endereço'),
                    new OA\Property(property: 'complemento_cliente', type: 'string', example: 'Apto 3',          description: 'Complemento (opcional)'),
                    new OA\Property(property: 'bairro_cliente',   type: 'string',  example: 'Centro',            description: 'Bairro do pagador'),
                    new OA\Property(property: 'cidade_cliente',   type: 'string',  example: 'Salvador',          description: 'Cidade do pagador'),
                    new OA\Property(property: 'estado_cliente',   type: 'string',  example: 'BA',                description: 'UF do pagador (2 letras)'),
                    new OA\Property(property: 'cep_cliente',      type: 'string',  example: '40000-000',         description: 'CEP do pagador'),
                    new OA\Property(property: 'metadata',         type: 'object',  example: ['tipo' => 'IPTU'],  description: 'Dados adicionais livres (opcional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Boleto emitido com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/BoletoResource')
            ),
            new OA\Response(response: 401, description: 'API Key inválida ou ausente'),
            new OA\Response(response: 403, description: 'Tenant inativo ou escopo insuficiente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 429, description: 'Rate limit atingido'),
            new OA\Response(response: 502, description: 'Falha na comunicação com o parceiro bancário'),
        ]
    )]
    public function store(IssueBoletoRequest $request): BoletoResource
    {
        $tenant = $request->attributes->get('tenant');
        $apiKey = $request->attributes->get('api_key');

        // Verifica escopo (RNF-13)
        if (! $apiKey->hasScope('boleto:write')) {
            abort(403, 'Esta API key não possui escopo boleto:write.');
        }

        $data = IssueBoletoData::fromRequest($request);

        $this->enforceApiKeyLimits($apiKey, $data);

        $boleto = $this->boletoService->issue($tenant, $data);

        return new BoletoResource($boleto->load('splits'));
    }

    private function enforceApiKeyLimits(ApiKey $apiKey, IssueBoletoData $data): void
    {
        // RF-AC-15: Limite de valor por boleto
        if ($apiKey->max_amount_cents && $data->amountCents > $apiKey->max_amount_cents) {
            $max = 'R$ ' . number_format($apiKey->max_amount_cents / 100, 2, ',', '.');
            abort(422, "Valor do boleto supera o limite máximo desta API key ({$max}).");
        }

        // RF-AC-18: Limite mensal de operações (soma dos registros diários do mês corrente)
        if ($apiKey->monthly_limit) {
            $monthlyCount = ApiKeyUsageDaily::where('api_key_id', $apiKey->id)
                ->whereYear('date', now()->year)
                ->whereMonth('date', now()->month)
                ->sum('count');

            if ($monthlyCount >= $apiKey->monthly_limit) {
                abort(429, 'Limite mensal de operações atingido para esta API key.');
            }
        }

        // RF-AC-15: Tipos de metadados permitidos
        if (! empty($apiKey->allowed_metadata_types) && ! empty($data->metadata)) {
            $disallowed = array_diff(array_keys($data->metadata), $apiKey->allowed_metadata_types);
            if (! empty($disallowed)) {
                abort(422, 'Chaves de metadados não permitidas por esta API key: ' . implode(', ', $disallowed) . '.');
            }
        }
    }

    #[OA\Get(
        path: '/boletos/{nossonumero}',
        summary: 'Consultar boleto',
        description: 'Retorna os dados de um boleto pelo seu ID interno (nossonumero).',
        security: [['ApiKey' => []]],
        tags: ['Boletos'],
        parameters: [
            new OA\Parameter(name: 'nossonumero', in: 'path', required: true, description: 'ID interno do boleto', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do boleto', content: new OA\JsonContent(ref: '#/components/schemas/BoletoResource')),
            new OA\Response(response: 401, description: 'API Key inválida ou ausente'),
            new OA\Response(response: 404, description: 'Boleto não encontrado ou pertence a outro tenant'),
        ]
    )]
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

    #[OA\Delete(
        path: '/boletos/{nossonumero}',
        summary: 'Cancelar boleto',
        description: 'Cancela um boleto pendente. Boletos pagos ou já cancelados não podem ser cancelados (RN-03).',
        security: [['ApiKey' => []]],
        tags: ['Boletos'],
        parameters: [
            new OA\Parameter(name: 'nossonumero', in: 'path', required: true, description: 'ID interno do boleto', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Boleto cancelado', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Boleto cancelado com sucesso.')])),
            new OA\Response(response: 401, description: 'API Key inválida ou ausente'),
            new OA\Response(response: 404, description: 'Boleto não encontrado ou pertence a outro tenant'),
            new OA\Response(response: 422, description: 'Boleto não pode ser cancelado (status atual não permite)'),
        ]
    )]
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
