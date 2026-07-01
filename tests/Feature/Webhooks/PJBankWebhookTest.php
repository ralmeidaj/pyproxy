<?php

namespace Tests\Feature\Webhooks;

use App\Enums\BoletoStatus;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Models\BankPartner;
use App\Models\Boleto;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PJBankWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Boleto      $boleto;
    private BoletoConfig $config;
    private string      $credencial = 'cred_pjbank_test_123';

    protected function setUp(): void
    {
        parent::setUp();

        // Sem chamadas HTTP reais — webhook outbound usa webhook_url=null → retorna cedo
        Http::fake();

        $partner = BankPartner::create([
            'name'     => 'PJBank',
            'slug'     => 'pjbank',
            'type'     => 'fintech',
            'status'   => 'active',
            'features' => ['boleto' => true, 'split' => true, 'dda' => true],
            'base_url' => 'https://api.pjbank.com.br',
        ]);

        $tenant = Tenant::create([
            'name'                => 'Tenant Webhook Test',
            'document'            => '12345678000195',
            'email'               => 'webhook@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto = app(CryptoService::class);

        $this->config = BoletoConfig::create([
            'tenant_id'             => $tenant->id,
            'bank_partner_id'       => $partner->id,
            'name'                  => 'Config Webhook Test',
            'is_default'            => true,
            'credentials_encrypted' => $crypto->encrypt(json_encode([
                'api_key' => $this->credencial,
                'chave'   => 'chave_test',
            ])),
            'status'              => 'active',
            'multa_percentual'    => 2.00,
            'juros_percentual_mes' => 1.00,
            'instrucoes'          => [],
            // webhook_url = null → dispatch() retorna cedo, sem chamada HTTP outbound
        ]);

        $this->boleto = Boleto::create([
            'tenant_id'        => $tenant->id,
            'boleto_config_id' => $this->config->id,
            'bank_partner_id'  => $partner->id,
            'external_ref'     => 'WH-001',
            'status'           => BoletoStatus::Pending,
            'amount_cents'     => 10000,
            'due_date'         => now()->addDays(7),
            'payer_name'       => 'Pagador Teste',
            'payer_document'   => '52998224725',
            'bank_boleto_id'   => 'NOSSO123',
            'digitable_line'   => '123.456',
            'pdf_url'          => 'https://example.com/boleto.pdf',
            'dda_registered'   => true,
            'config_snapshot'  => ['multa' => 2.00],
            'splits_snapshot'  => [],
        ]);
    }

    private function webhookPayload(array $overrides = []): array
    {
        return array_merge([
            'nosso_numero'    => 'NOSSO123',
            'credencial'      => $this->credencial,
            'valor'           => '100.00',
            'forma_pagamento' => 'pix',
        ], $overrides);
    }

    // ─── Credencial válida ──────────────────────────────────────────────────

    public function test_valid_credencial_marks_boleto_as_paid(): void
    {
        $this->postJson('/api/webhooks/pjbank', $this->webhookPayload())
            ->assertStatus(200)
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('boletos', [
            'id'           => $this->boleto->id,
            'status'       => BoletoStatus::Paid->value,
            'paid_channel' => 'pix',
        ]);
    }

    // ─── Credencial inválida ────────────────────────────────────────────────

    public function test_invalid_credencial_returns_401_and_does_not_mark_paid(): void
    {
        $this->postJson('/api/webhooks/pjbank', $this->webhookPayload([
            'credencial' => 'credencial_errada',
        ]))->assertStatus(401);

        $this->assertDatabaseHas('boletos', [
            'id'     => $this->boleto->id,
            'status' => BoletoStatus::Pending->value,
        ]);
    }

    // ─── Sem campo credencial — fail-closed (C2) ───────────────────────────

    public function test_missing_credencial_field_returns_401(): void
    {
        $payload = $this->webhookPayload();
        unset($payload['credencial']);

        $this->postJson('/api/webhooks/pjbank', $payload)
            ->assertStatus(401);

        // boleto não deve ser marcado como pago
        $this->assertDatabaseHas('boletos', [
            'id'     => $this->boleto->id,
            'status' => BoletoStatus::Pending->value,
        ]);
    }

    // ─── Sem nosso_numero ──────────────────────────────────────────────────

    public function test_missing_nosso_numero_returns_200_without_processing(): void
    {
        $this->postJson('/api/webhooks/pjbank', ['credencial' => $this->credencial])
            ->assertStatus(200);

        $this->assertDatabaseHas('boletos', [
            'id'     => $this->boleto->id,
            'status' => BoletoStatus::Pending->value,
        ]);
    }

    // ─── Idempotência ──────────────────────────────────────────────────────

    public function test_duplicate_notification_is_ignored(): void
    {
        $payload = $this->webhookPayload();

        $this->postJson('/api/webhooks/pjbank', $payload)->assertStatus(200);
        $this->postJson('/api/webhooks/pjbank', $payload)->assertStatus(200);

        $this->assertDatabaseCount('boletos', 1);
        $this->assertDatabaseHas('boletos', ['status' => BoletoStatus::Paid->value]);
    }
}
