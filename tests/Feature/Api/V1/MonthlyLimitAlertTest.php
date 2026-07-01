<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Jobs\AlertMonthlyLimitJob;
use App\Models\ApiKey;
use App\Models\ApiKeyUsageDaily;
use App\Models\BackofficeUser;
use App\Models\BankPartner;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonthlyLimitAlertTest extends TestCase
{
    use RefreshDatabase;

    private string $plainKey;
    private ApiKey $apiKey;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.pjbank.com.br/*' => Http::response([
                'nossonumero'       => '99999999',
                'token_facilitador' => 'tok_test',
                'credencial'        => 'cred_fake',
                'linkBoleto'        => 'https://link/fake',
                'linhaDigitavel'    => '1234',
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
            'name'                => 'Tenant Alert Test',
            'document'            => '12345678000195',
            'email'               => 'alert@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto = app(CryptoService::class);

        BoletoConfig::create([
            'tenant_id'             => $this->tenant->id,
            'bank_partner_id'       => $partner->id,
            'name'                  => 'Config Alert Test',
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
            'name'                  => 'Alert Test Key',
            'key_prefix'            => substr($plainKey, 0, 12),
            'key_hash'              => $crypto->hashApiKey($plainKey),
            'scopes'                => ['boleto:write', 'boleto:read'],
            'rate_limit_per_minute' => 200,
            'monthly_limit'         => 100,
        ]);
    }

    private function headers(): array
    {
        return ['X-Api-Key' => $this->plainKey];
    }

    public function test_dispatches_alert_job_when_80_percent_reached(): void
    {
        Queue::fake();

        // Simula 79 emissões já realizadas neste mês
        ApiKeyUsageDaily::create([
            'api_key_id' => $this->apiKey->id,
            'date'       => now()->toDateString(),
            'count'      => 79,
        ]);

        // A próxima requisição incrementa para 80 → exatamente 80%
        $this->postJson('/api/v1/boletos', $this->boletoPayload('ALERT-001'), $this->headers())
            ->assertStatus(201);

        Queue::assertPushed(AlertMonthlyLimitJob::class);
    }

    public function test_does_not_dispatch_alert_below_80_percent(): void
    {
        Queue::fake();

        // Simula 78 emissões — depois do increment fica 79 (79% de 100)
        ApiKeyUsageDaily::create([
            'api_key_id' => $this->apiKey->id,
            'date'       => now()->toDateString(),
            'count'      => 78,
        ]);

        $this->postJson('/api/v1/boletos', $this->boletoPayload('BELOW-001'), $this->headers())
            ->assertStatus(201);

        Queue::assertNotPushed(AlertMonthlyLimitJob::class);
    }

    public function test_dispatches_alert_only_once_per_month(): void
    {
        Queue::fake();

        ApiKeyUsageDaily::create([
            'api_key_id' => $this->apiKey->id,
            'date'       => now()->toDateString(),
            'count'      => 84,
        ]);

        // Primeira requisição — deve disparar o alerta
        $this->postJson('/api/v1/boletos', $this->boletoPayload('ONCE-001'), $this->headers())
            ->assertStatus(201);

        // Segunda requisição — já está acima de 80%, mas cache impede segundo disparo
        $this->postJson('/api/v1/boletos', $this->boletoPayload('ONCE-002'), $this->headers())
            ->assertStatus(201);

        Queue::assertPushed(AlertMonthlyLimitJob::class, 1);
    }

    public function test_does_not_dispatch_alert_when_no_monthly_limit(): void
    {
        Queue::fake();

        // Atualiza a key para não ter limite mensal
        $this->apiKey->update(['monthly_limit' => null]);

        ApiKeyUsageDaily::create([
            'api_key_id' => $this->apiKey->id,
            'date'       => now()->toDateString(),
            'count'      => 9999,
        ]);

        $this->postJson('/api/v1/boletos', $this->boletoPayload('NOLIMIT-001'), $this->headers())
            ->assertStatus(201);

        Queue::assertNotPushed(AlertMonthlyLimitJob::class);
    }

    private function boletoPayload(string $pedidoNumero): array
    {
        return [
            'pedido_numero'    => $pedidoNumero,
            'valor'            => '100.00',
            'vencimento'       => '07/30/2026',
            'nome_cliente'     => 'João Silva',
            'cpf_cliente'      => '529.982.247-25',
            'endereco_cliente' => 'Rua Teste',
            'numero_cliente'   => '10',
            'bairro_cliente'   => 'Centro',
            'cidade_cliente'   => 'Salvador',
            'estado_cliente'   => 'BA',
            'cep_cliente'      => '40000-000',
        ];
    }
}
