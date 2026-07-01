<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BatchStatus;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Jobs\ProcessBatchJob;
use App\Models\ApiKey;
use App\Models\BankPartner;
use App\Models\Boleto;
use App\Models\BoletoConfig;
use App\Models\BoletosBatch;
use App\Models\Tenant;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class BatchControllerTest extends TestCase
{
    use RefreshDatabase;

    private string       $plainKey;
    private ApiKey       $apiKey;
    private Tenant       $tenant;
    private BoletoConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.pjbank.com.br/*' => Http::response([
                'status'            => '201',
                'msg'               => 'Sucesso.',
                'nossonumero'       => '99999999',
                'banco_numero'      => '481',
                'token_facilitador' => 'tok_test_fake',
                'credencial'        => 'cred_fake',
                'linkBoleto'        => 'https://api.pjbank.com.br/boletos/fake123',
                'linhaDigitavel'    => '48190.00003 00005.150552 99999.990147 9 15030000010000',
            ], 201),
        ]);

        $partner = BankPartner::create([
            'name'     => 'PJBank',
            'slug'     => 'pjbank',
            'type'     => 'fintech',
            'status'   => 'active',
            'features' => ['boleto' => true, 'split' => true, 'dda' => true],
            'base_url' => 'https://api.pjbank.com.br',
        ]);

        $this->tenant = Tenant::create([
            'name'                => 'Tenant Batch Test',
            'document'            => '12345678000195',
            'email'               => 'batch@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto = app(CryptoService::class);

        $this->config = BoletoConfig::create([
            'tenant_id'             => $this->tenant->id,
            'bank_partner_id'       => $partner->id,
            'name'                  => 'Config Batch Test',
            'is_default'            => true,
            'credentials_encrypted' => $crypto->encrypt(json_encode([
                'api_key' => 'cred_fake',
                'chave'   => 'chave_fake',
            ])),
            'status'                => 'active',
            'multa_percentual'      => 2.00,
            'juros_percentual_mes'  => 1.00,
            'desconto_percentual'   => 0,
            'instrucoes'            => [],
        ]);

        $plainKey       = 'ppx_' . Str::random(40);
        $this->plainKey = $plainKey;

        $this->apiKey = ApiKey::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Batch Test Key',
            'key_prefix'            => substr($plainKey, 0, 12),
            'key_hash'              => $crypto->hashApiKey($plainKey),
            'scopes'                => ['boleto:write', 'boleto:read'],
            'rate_limit_per_minute' => 200,
        ]);
    }

    private function headers(?string $key = null): array
    {
        return ['X-Api-Key' => $key ?? $this->plainKey];
    }

    private function boletoItem(array $overrides = []): array
    {
        return array_merge([
            'pedido_numero'    => 'BATCH-001',
            'valor'            => '100.00',
            'vencimento'       => '07/30/2026',
            'nome_cliente'     => 'João Silva',
            'cpf_cliente'      => '529.982.247-25',
            'email_cliente'    => 'joao@example.com',
            'endereco_cliente' => 'Rua Teste',
            'numero_cliente'   => '10',
            'bairro_cliente'   => 'Centro',
            'cidade_cliente'   => 'Salvador',
            'estado_cliente'   => 'BA',
            'cep_cliente'      => '40000-000',
        ], $overrides);
    }

    // ─── POST /api/v1/boletos/batch ────────────────────────────────────────

    public function test_store_creates_batch_and_returns_202(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/boletos/batch', [
            'external_ref' => 'LOTE-001',
            'boletos'      => [$this->boletoItem()],
        ], $this->headers());

        $response->assertStatus(202)
            ->assertJsonStructure([
                'id', 'external_ref', 'status', 'status_label',
                'total_count', 'processed_count', 'success_count', 'error_count',
                'created_at',
            ])
            ->assertJsonPath('external_ref', 'LOTE-001')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total_count', 1);

        $this->assertDatabaseHas('boletos_batches', [
            'external_ref' => 'LOTE-001',
            'status'       => 'pending',
            'total_count'  => 1,
            'tenant_id'    => $this->tenant->id,
        ]);

        Queue::assertPushed(ProcessBatchJob::class);
    }

    public function test_store_is_idempotent_by_external_ref(): void
    {
        Queue::fake();

        $payload = [
            'external_ref' => 'LOTE-IDEM',
            'boletos'      => [$this->boletoItem()],
        ];

        $first  = $this->postJson('/api/v1/boletos/batch', $payload, $this->headers());
        $second = $this->postJson('/api/v1/boletos/batch', $payload, $this->headers());

        $first->assertStatus(202);
        $second->assertStatus(200);
        $second->assertJsonPath('id', $first->json('id'));

        $this->assertDatabaseCount('boletos_batches', 1);
        Queue::assertPushed(ProcessBatchJob::class, 1); // só dispatch uma vez
    }

    public function test_store_rejects_invalid_api_key(): void
    {
        $this->postJson('/api/v1/boletos/batch', [
            'external_ref' => 'LOTE-X',
            'boletos'      => [$this->boletoItem()],
        ], $this->headers('invalid-key'))
            ->assertStatus(401);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/boletos/batch', [
            'external_ref' => 'LOTE-VAL',
            'boletos'      => [
                ['pedido_numero' => 'X'],  // faltam campos obrigatórios
            ],
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['boletos.0.valor', 'boletos.0.vencimento', 'boletos.0.nome_cliente']);
    }

    public function test_store_rejects_more_than_500_boletos(): void
    {
        Queue::fake();

        $items = array_fill(0, 501, $this->boletoItem());

        $this->postJson('/api/v1/boletos/batch', [
            'external_ref' => 'LOTE-BIG',
            'boletos'      => $items,
        ], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['boletos']);
    }

    public function test_store_requires_boleto_write_scope(): void
    {
        Queue::fake();

        $crypto   = app(CryptoService::class);
        $plainKey = 'ppx_' . Str::random(40);

        ApiKey::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Read-only key',
            'key_prefix'            => substr($plainKey, 0, 12),
            'key_hash'              => $crypto->hashApiKey($plainKey),
            'scopes'                => ['boleto:read'],
            'rate_limit_per_minute' => 200,
        ]);

        $this->postJson('/api/v1/boletos/batch', [
            'external_ref' => 'LOTE-SCOPE',
            'boletos'      => [$this->boletoItem()],
        ], $this->headers($plainKey))
            ->assertStatus(403);
    }

    // ─── GET /api/v1/boletos/batch/{batch} ────────────────────────────────

    public function test_show_returns_batch_status(): void
    {
        $batch = BoletosBatch::create([
            'tenant_id'   => $this->tenant->id,
            'api_key_id'  => $this->apiKey->id,
            'external_ref' => 'LOTE-SHOW',
            'status'      => BatchStatus::Completed,
            'total_count' => 2,
            'processed_count' => 2,
            'success_count' => 2,
            'error_count' => 0,
            'items'       => [$this->boletoItem(['pedido_numero' => 'A']), $this->boletoItem(['pedido_numero' => 'B'])],
            'results'     => [
                'A' => ['status' => 'success', 'boleto_id' => 1],
                'B' => ['status' => 'success', 'boleto_id' => 2],
            ],
        ]);

        $this->getJson("/api/v1/boletos/batch/{$batch->id}", $this->headers())
            ->assertStatus(200)
            ->assertJsonPath('id', $batch->id)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('success_count', 2)
            ->assertJsonPath('error_count', 0)
            ->assertJsonStructure(['results']);
    }

    public function test_show_returns_404_for_other_tenant_batch(): void
    {
        $otherTenant = Tenant::create([
            'name'                => 'Outro Tenant',
            'document'            => '98765432000100',
            'email'               => 'outro@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto   = app(CryptoService::class);
        $otherKey = 'ppx_' . Str::random(40);

        $otherApiKey = ApiKey::create([
            'tenant_id'             => $otherTenant->id,
            'name'                  => 'Other Key',
            'key_prefix'            => substr($otherKey, 0, 12),
            'key_hash'              => $crypto->hashApiKey($otherKey),
            'scopes'                => ['boleto:read'],
            'rate_limit_per_minute' => 200,
        ]);

        $batch = BoletosBatch::create([
            'tenant_id'   => $otherTenant->id,
            'api_key_id'  => $otherApiKey->id,
            'external_ref' => 'LOTE-OTHER',
            'status'      => BatchStatus::Completed,
            'total_count' => 1,
            'items'       => [$this->boletoItem()],
        ]);

        // Nosso tenant tentando ver o lote do outro
        $this->getJson("/api/v1/boletos/batch/{$batch->id}", $this->headers())
            ->assertStatus(404);
    }

    // ─── ProcessBatchJob (integração síncrona via QUEUE_CONNECTION=sync) ──

    public function test_process_batch_job_issues_boletos_and_marks_completed(): void
    {
        $batch = BoletosBatch::create([
            'tenant_id'   => $this->tenant->id,
            'api_key_id'  => $this->apiKey->id,
            'external_ref' => 'LOTE-JOB',
            'status'      => BatchStatus::Pending,
            'total_count' => 2,
            'items'       => [
                $this->boletoItem(['pedido_numero' => 'JOB-001']),
                $this->boletoItem(['pedido_numero' => 'JOB-002', 'valor' => '200.00']),
            ],
        ]);

        // Executa o job de forma síncrona (sem fila)
        (new ProcessBatchJob($batch->id))->handle(app(\App\Services\BoletoService::class));

        $batch->refresh();

        $this->assertEquals(BatchStatus::Completed, $batch->status);
        $this->assertEquals(2, $batch->total_count);
        $this->assertEquals(2, $batch->processed_count);
        $this->assertEquals(2, $batch->success_count);
        $this->assertEquals(0, $batch->error_count);
        $this->assertNotNull($batch->finished_at);

        $this->assertArrayHasKey('JOB-001', $batch->results);
        $this->assertArrayHasKey('JOB-002', $batch->results);
        $this->assertEquals('success', $batch->results['JOB-001']['status']);
        $this->assertEquals('success', $batch->results['JOB-002']['status']);

        $this->assertDatabaseCount('boletos', 2);
    }

    public function test_process_batch_job_marks_partial_on_mixed_results(): void
    {
        $batch = BoletosBatch::create([
            'tenant_id'    => $this->tenant->id,
            'api_key_id'   => $this->apiKey->id,
            'external_ref' => 'LOTE-PARTIAL',
            'status'       => BatchStatus::Pending,
            'total_count'  => 2,
            'items'        => [
                $this->boletoItem(['pedido_numero' => 'OK-001']),
                $this->boletoItem(['pedido_numero' => 'FAIL-001']),
            ],
        ]);

        // Mock BoletoService: primeiro item sucede, segundo lança exceção
        $fakeBoleto = (new Boleto())->forceFill([
            'id'             => 99,
            'bank_boleto_id' => '111',
            'barcode'        => '111',
            'digitable_line' => '111',
            'pix_qr_code'    => null,
            'pdf_url'        => null,
            'amount_cents'   => 10000,
            'due_date'       => '2026-07-30',
        ]);

        $callCount   = 0;
        $mockService = $this->createMock(\App\Services\BoletoService::class);
        $mockService->expects($this->exactly(2))
            ->method('issue')
            ->willReturnCallback(function () use (&$callCount, $fakeBoleto) {
                $callCount++;
                if ($callCount === 1) {
                    return $fakeBoleto;
                }
                throw new \App\Exceptions\BankPartnerException('PJBank: HTTP 400');
            });

        (new ProcessBatchJob($batch->id))->handle($mockService);

        $batch->refresh();

        $this->assertEquals(BatchStatus::Partial, $batch->status);
        $this->assertEquals(2, $batch->processed_count);
        $this->assertEquals(1, $batch->success_count);
        $this->assertEquals(1, $batch->error_count);
        $this->assertNotNull($batch->results);
        $this->assertEquals('success', $batch->results['OK-001']['status']);
        $this->assertEquals('error', $batch->results['FAIL-001']['status']);
        $this->assertStringContainsString('HTTP 400', $batch->results['FAIL-001']['message']);
    }

    public function test_process_batch_job_is_idempotent_for_final_status(): void
    {
        $batch = BoletosBatch::create([
            'tenant_id'    => $this->tenant->id,
            'api_key_id'   => $this->apiKey->id,
            'external_ref' => 'LOTE-IDEM-JOB',
            'status'       => BatchStatus::Completed, // já finalizado
            'total_count'  => 1,
            'items'        => [$this->boletoItem()],
        ]);

        // Job não deve re-emitir boletos quando já finalizado
        (new ProcessBatchJob($batch->id))->handle(app(\App\Services\BoletoService::class));

        $this->assertDatabaseCount('boletos', 0); // nenhum boleto criado
    }
}
