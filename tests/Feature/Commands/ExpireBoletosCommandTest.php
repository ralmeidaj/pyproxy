<?php

namespace Tests\Feature\Commands;

use App\Enums\BoletoStatus;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Models\BankPartner;
use App\Models\Boleto;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\CryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireBoletosCommandTest extends TestCase
{
    use RefreshDatabase;

    private BankPartner  $partner;
    private Tenant       $tenant;
    private BoletoConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = BankPartner::create([
            'name' => 'PJBank', 'slug' => 'pjbank', 'type' => 'fintech',
            'status' => 'active', 'features' => [], 'base_url' => 'https://api.pjbank.com.br',
        ]);
        $this->tenant = Tenant::create([
            'name' => 'Tenant Expire', 'document' => '12345678000195',
            'email' => 'expire@test.com', 'status' => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);
        $crypto = app(CryptoService::class);
        $this->config = BoletoConfig::create([
            'tenant_id' => $this->tenant->id, 'bank_partner_id' => $this->partner->id,
            'name' => 'Expire Config', 'is_default' => true,
            'credentials_encrypted' => $crypto->encrypt(json_encode(['api_key' => 'x', 'chave' => 'y'])),
            'status' => 'active', 'multa_percentual' => 0, 'juros_percentual_mes' => 0, 'instrucoes' => [],
        ]);
    }

    private function createBoleto(array $attrs = []): Boleto
    {
        return Boleto::create(array_merge([
            'tenant_id'        => $this->tenant->id,
            'boleto_config_id' => $this->config->id,
            'bank_partner_id'  => $this->partner->id,
            'external_ref'     => 'EXP-' . uniqid(),
            'status'           => BoletoStatus::Pending,
            'amount_cents'     => 5000,
            'due_date'         => now()->subDay(),
            'payer_name'       => 'Pagador',
            'payer_document'   => '52998224725',
            'bank_boleto_id'   => 'BID-' . uniqid(),
            'digitable_line'   => '123',
            'pdf_url'          => 'http://x.com/b.pdf',
            'dda_registered'   => false,
            'config_snapshot'  => [],
            'splits_snapshot'  => [],
        ], $attrs));
    }

    public function test_expires_pending_boletos_with_past_due_date(): void
    {
        $expired1 = $this->createBoleto(['due_date' => now()->subDays(2)]);
        $expired2 = $this->createBoleto(['due_date' => now()->subDay()]);

        $this->artisan('boletos:expire')->assertExitCode(0);

        $this->assertDatabaseHas('boletos', ['id' => $expired1->id, 'status' => BoletoStatus::Expired->value]);
        $this->assertDatabaseHas('boletos', ['id' => $expired2->id, 'status' => BoletoStatus::Expired->value]);
    }

    public function test_does_not_expire_boleto_with_future_due_date(): void
    {
        $future = $this->createBoleto(['due_date' => now()->addDay()]);

        $this->artisan('boletos:expire')->assertExitCode(0);

        $this->assertDatabaseHas('boletos', ['id' => $future->id, 'status' => BoletoStatus::Pending->value]);
    }

    public function test_does_not_expire_boleto_already_paid(): void
    {
        $paid = $this->createBoleto([
            'due_date' => now()->subDay(),
            'status'   => BoletoStatus::Paid,
        ]);

        $this->artisan('boletos:expire')->assertExitCode(0);

        $this->assertDatabaseHas('boletos', ['id' => $paid->id, 'status' => BoletoStatus::Paid->value]);
    }

    public function test_does_not_expire_boleto_due_today(): void
    {
        $today = $this->createBoleto(['due_date' => today()]);

        $this->artisan('boletos:expire')->assertExitCode(0);

        $this->assertDatabaseHas('boletos', ['id' => $today->id, 'status' => BoletoStatus::Pending->value]);
    }

    public function test_outputs_count_of_expired_boletos(): void
    {
        $this->createBoleto(['due_date' => now()->subDays(3)]);
        $this->createBoleto(['due_date' => now()->subDays(1)]);

        $this->artisan('boletos:expire')
            ->expectsOutput('Boletos expirados: 2')
            ->assertExitCode(0);
    }

    public function test_outputs_message_when_nothing_to_expire(): void
    {
        $this->artisan('boletos:expire')
            ->expectsOutput('Nenhum boleto para expirar.')
            ->assertExitCode(0);
    }
}
