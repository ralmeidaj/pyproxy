<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BoletoStatus;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Models\ApiKey;
use App\Models\BankPartner;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoletoControllerTest extends TestCase
{
    use RefreshDatabase;

    private string      $plainKey;
    private ApiKey      $apiKey;
    private Tenant      $tenant;
    private BoletoConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Intercepta chamadas ao PJBank para não fazer requests reais
        Http::fake([
            'api.pjbank.com.br/*' => Http::response([
                'status'            => '201',
                'msg'               => 'Sucesso.',
                'nossonumero'       => '99999999',
                'banco_numero'      => '481',
                'token_facilitador' => 'tok_test_fake',
                'credencial'        => 'cred_fake',
                'linkBoleto'        => 'https://api.pjbank.com.br/boletos/fake123?formato=pix',
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
            'name'                => 'Tenant Feature Test',
            'document'            => '12345678000195',
            'email'               => 'tenant@feature.test',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto = app(CryptoService::class);

        $this->config = BoletoConfig::create([
            'tenant_id'              => $this->tenant->id,
            'bank_partner_id'        => $partner->id,
            'name'                   => 'Config Feature Test',
            'is_default'             => true,
            'credentials_encrypted'  => $crypto->encrypt(json_encode([
                'api_key' => 'cred_fake',
                'chave'   => 'chave_fake',
            ])),
            'status'                 => 'active',
            'multa_percentual'       => 2.00,
            'juros_percentual_mes'   => 1.00,
            'desconto_percentual'    => 0,
            'instrucoes'             => [],
        ]);

        $plainKey       = 'ppx_' . Str::random(40);
        $this->plainKey = $plainKey;

        $this->apiKey = ApiKey::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Feature Test Key',
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'pedido_numero'    => 'FEAT-001',
            'valor'            => '100.00',
            'vencimento'       => '07/30/2026',
            'nome_cliente'     => 'João Silva',
            'cpf_cliente'      => '529.982.247-25',
            'email_cliente'    => 'joao@example.com',
            'telefone_cliente' => '71999990000',
            'endereco_cliente' => 'Rua Teste',
            'numero_cliente'   => '10',
            'bairro_cliente'   => 'Centro',
            'cidade_cliente'   => 'Salvador',
            'estado_cliente'   => 'BA',
            'cep_cliente'      => '40000-000',
        ], $overrides);
    }

    // ─── POST /api/v1/boletos ───────────────────────────────────────────────

    public function test_store_creates_boleto_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/boletos', $this->payload(), $this->headers());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'nossonumero', 'pedido_numero', 'status',
                    'valor', 'vencimento', 'linhaDigitavel', 'linkBoleto', 'dda_registered',
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.pedido_numero', 'FEAT-001')
            ->assertJsonPath('data.valor', '100.00')
            ->assertJsonPath('data.dda_registered', true);

        $this->assertDatabaseHas('boletos', [
            'external_ref' => 'FEAT-001',
            'status'       => 'pending',
            'amount_cents' => 10000,
        ]);
    }

    public function test_store_returns_401_when_api_key_missing(): void
    {
        $this->postJson('/api/v1/boletos', $this->payload())
            ->assertStatus(401)
            ->assertJsonPath('message', 'API key não informada.');
    }

    public function test_store_returns_401_when_api_key_invalid(): void
    {
        $this->postJson('/api/v1/boletos', $this->payload(), $this->headers('ppx_invalida_key_123456789012345678901234567890'))
            ->assertStatus(401)
            ->assertJsonPath('message', 'API key inválida ou revogada.');
    }

    public function test_store_returns_403_when_tenant_inactive(): void
    {
        $this->tenant->update(['status' => TenantStatus::Suspended]);

        $this->postJson('/api/v1/boletos', $this->payload(), $this->headers())
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_required_fields_missing(): void
    {
        $this->postJson('/api/v1/boletos', [], $this->headers())
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    public function test_store_is_idempotent_for_same_external_ref(): void
    {
        $payload = $this->payload(['pedido_numero' => 'IDEM-001']);

        $first  = $this->postJson('/api/v1/boletos', $payload, $this->headers());
        $second = $this->postJson('/api/v1/boletos', $payload, $this->headers());

        $first->assertStatus(201);
        $second->assertSuccessful(); // 200 — boleto existente (wasRecentlyCreated=false)

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
            'Idempotência: boletos devem ser o mesmo objeto'
        );

        $this->assertDatabaseCount('boletos', 1);
    }

    // ─── GET /api/v1/boletos/{boleto} ──────────────────────────────────────

    public function test_show_returns_boleto_for_owner_tenant(): void
    {
        $issue = $this->postJson('/api/v1/boletos', $this->payload(['pedido_numero' => 'SHOW-001']), $this->headers());
        $issue->assertStatus(201);

        $id = $issue->json('data.id');

        $this->getJson("/api/v1/boletos/{$id}", $this->headers())
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.pedido_numero', 'SHOW-001');
    }

    public function test_show_returns_404_for_other_tenants_boleto(): void
    {
        // Cria outro tenant com sua própria API key
        $crypto    = app(CryptoService::class);
        $otherKey  = 'ppx_' . Str::random(40);
        $otherTenant = Tenant::create([
            'name'                => 'Outro Tenant',
            'document'            => '98765432000111',
            'email'               => 'outro@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);
        $partner = BankPartner::where('slug', 'pjbank')->first();
        BoletoConfig::create([
            'tenant_id'             => $otherTenant->id,
            'bank_partner_id'       => $partner->id,
            'name'                  => 'Outro Config',
            'is_default'            => true,
            'credentials_encrypted' => $crypto->encrypt(json_encode(['api_key' => 'x', 'chave' => 'y'])),
            'status'                => 'active',
            'multa_percentual'      => 0,
            'juros_percentual_mes'  => 0,
            'instrucoes'            => [],
        ]);
        ApiKey::create([
            'tenant_id'             => $otherTenant->id,
            'name'                  => 'Outro Key',
            'key_prefix'            => substr($otherKey, 0, 12),
            'key_hash'              => $crypto->hashApiKey($otherKey),
            'scopes'                => ['boleto:write', 'boleto:read'],
            'rate_limit_per_minute' => 200,
        ]);

        // Emite boleto pelo tenant original
        $issue = $this->postJson('/api/v1/boletos', $this->payload(['pedido_numero' => 'ISOL-001']), $this->headers());
        $id    = $issue->json('data.id');

        // Outro tenant tenta acessar
        $this->getJson("/api/v1/boletos/{$id}", $this->headers($otherKey))
            ->assertStatus(404);
    }

    // ─── DELETE /api/v1/boletos/{boleto} ───────────────────────────────────

    public function test_destroy_cancels_pending_boleto(): void
    {
        Http::fake([
            'api.pjbank.com.br/*' => Http::sequence()
                ->push(['status' => '201', 'msg' => 'Sucesso.', 'nossonumero' => '88888', 'linhaDigitavel' => 'line', 'linkBoleto' => 'http://link'], 201)
                ->push('', 200), // cancelamento
        ]);

        $issue = $this->postJson('/api/v1/boletos', $this->payload(['pedido_numero' => 'CANCEL-001']), $this->headers());
        $id    = $issue->json('data.id');

        $this->deleteJson("/api/v1/boletos/{$id}", [], $this->headers())
            ->assertStatus(200)
            ->assertJsonPath('message', 'Boleto cancelado com sucesso.');

        $this->assertDatabaseHas('boletos', [
            'id'     => $id,
            'status' => BoletoStatus::Cancelled->value,
        ]);
    }
}
