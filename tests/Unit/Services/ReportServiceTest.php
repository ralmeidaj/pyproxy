<?php

namespace Tests\Unit\Services;

use App\Enums\BoletoStatus;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Models\BankPartner;
use App\Models\Boleto;
use App\Models\BoletoConfig;
use App\Models\Tenant;
use App\Services\CryptoService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private Tenant        $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReportService::class);

        $partner = BankPartner::create([
            'name'     => 'PJBank',
            'slug'     => 'pjbank',
            'type'     => 'fintech',
            'status'   => 'active',
            'features' => ['boleto' => true, 'split' => true, 'dda' => true],
            'base_url' => 'https://api.pjbank.com.br',
        ]);

        $this->tenant = Tenant::create([
            'name'                => 'Tenant Report Test',
            'document'            => '12345678000195',
            'email'               => 'report@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        $crypto = app(CryptoService::class);

        $this->config = BoletoConfig::create([
            'tenant_id'             => $this->tenant->id,
            'bank_partner_id'       => $partner->id,
            'name'                  => 'Config Report Test',
            'is_default'            => true,
            'credentials_encrypted' => $crypto->encrypt(json_encode(['api_key' => 'x', 'chave' => 'y'])),
            'status'                => 'active',
            'multa_percentual'      => 2.00,
            'juros_percentual_mes'  => 1.00,
            'desconto_percentual'   => 0,
            'instrucoes'            => [],
        ]);
    }

    private function createBoleto(array $attrs = []): Boleto
    {
        $boleto = Boleto::create(array_merge([
            'tenant_id'        => $this->tenant->id,
            'boleto_config_id' => $this->config->id,
            'bank_partner_id'  => $this->config->bank_partner_id,
            'external_ref'     => uniqid('REF-'),
            'status'           => BoletoStatus::Pending,
            'amount_cents'     => 10000,
            'due_date'         => now()->addDays(7)->toDateString(),
            'payer_name'       => 'Pagador',
            'payer_document'   => '52998224725',
            'bank_boleto_id'   => uniqid('NOSSO-'),
            'digitable_line'   => '123',
            'dda_registered'   => true,
            'config_snapshot'  => [],
            'splits_snapshot'  => [],
        ], $attrs));

        // Eloquent sobrescreve created_at com now() — forçar via DB raw após save
        if (isset($attrs['created_at'])) {
            DB::table('boletos')->where('id', $boleto->id)->update(['created_at' => $attrs['created_at']]);
        }

        return $boleto->refresh();
    }

    // ─── timeSeries — daily ──────────────────────────────────────────────────

    public function test_time_series_daily_groups_by_day(): void
    {
        Carbon::setTestNow('2026-07-01 12:00:00');

        $this->createBoleto(['created_at' => '2026-07-01 10:00:00']);
        $this->createBoleto(['created_at' => '2026-07-01 11:00:00']);
        $this->createBoleto(['created_at' => '2026-07-02 10:00:00']);

        $result = $this->service->timeSeries($this->tenant, '2026-07-01', '2026-07-03', 'daily');

        $this->assertCount(2, $result);
        $this->assertEquals('2026-07-01', $result[0]['period']);
        $this->assertEquals(2, $result[0]['issued']);
        $this->assertEquals('2026-07-02', $result[1]['period']);
        $this->assertEquals(1, $result[1]['issued']);

        Carbon::setTestNow();
    }

    public function test_time_series_counts_paid_boletos_separately(): void
    {
        $this->createBoleto([
            'created_at'        => '2026-07-01 10:00:00',
            'status'            => BoletoStatus::Paid,
            'paid_at'           => '2026-07-01 11:00:00',
            'paid_amount_cents' => 9900,
        ]);
        $this->createBoleto(['created_at' => '2026-07-01 10:00:00']);

        $result = $this->service->timeSeries($this->tenant, '2026-07-01', '2026-07-01', 'daily');

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['issued']);
        $this->assertEquals(1, $result[0]['paid']);
        $this->assertEquals(9900, $result[0]['amount_paid_cents']);
    }

    public function test_time_series_monthly_groups_by_month(): void
    {
        $this->createBoleto(['created_at' => '2026-06-15 10:00:00', 'amount_cents' => 5000]);
        $this->createBoleto(['created_at' => '2026-07-01 10:00:00', 'amount_cents' => 10000]);
        $this->createBoleto(['created_at' => '2026-07-20 10:00:00', 'amount_cents' => 10000]);

        $result = $this->service->timeSeries($this->tenant, '2026-06-01', '2026-07-31', 'monthly');

        $this->assertCount(2, $result);

        $june = collect($result)->firstWhere(fn ($r) => str_starts_with($r['period'], '2026-06'));
        $july = collect($result)->firstWhere(fn ($r) => str_starts_with($r['period'], '2026-07'));

        $this->assertEquals(1, $june['issued']);
        $this->assertEquals(5000, $june['amount_issued_cents']);
        $this->assertEquals(2, $july['issued']);
        $this->assertEquals(20000, $july['amount_issued_cents']);
    }

    public function test_time_series_returns_empty_when_no_boletos(): void
    {
        $result = $this->service->timeSeries($this->tenant, '2026-07-01', '2026-07-31', 'daily');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_time_series_filters_by_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name'                => 'Outro',
            'document'            => '98765432000100',
            'email'               => 'outro@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        // 1 boleto para $this->tenant
        $this->createBoleto(['created_at' => '2026-07-01 10:00:00']);

        // 1 boleto para o outro tenant — criado diretamente sem usar createBoleto()
        $other = Boleto::create([
            'tenant_id'        => $otherTenant->id,
            'boleto_config_id' => $this->config->id,
            'bank_partner_id'  => $this->config->bank_partner_id,
            'external_ref'     => 'OTHER-001',
            'status'           => BoletoStatus::Pending,
            'amount_cents'     => 10000,
            'due_date'         => now()->addDays(7)->toDateString(),
            'payer_name'       => 'Outro Pagador',
            'payer_document'   => '52998224725',
            'bank_boleto_id'   => 'NOSSO-OTHER',
            'digitable_line'   => '456',
            'dda_registered'   => true,
            'config_snapshot'  => [],
            'splits_snapshot'  => [],
        ]);
        DB::table('boletos')->where('id', $other->id)->update(['created_at' => '2026-07-01 12:00:00']);

        $result = $this->service->timeSeries($this->tenant, '2026-07-01', '2026-07-31', 'daily');

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['issued']); // só o do $this->tenant
    }

    public function test_time_series_with_null_tenant_returns_all(): void
    {
        $this->createBoleto(['created_at' => '2026-07-01 10:00:00']);
        $this->createBoleto(['created_at' => '2026-07-01 12:00:00']);

        $result = $this->service->timeSeries(null, '2026-07-01', '2026-07-31', 'daily');

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['issued']);
    }

    // ─── byMetadata ──────────────────────────────────────────────────────────

    public function test_by_metadata_groups_by_key_value(): void
    {
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'amount_cents' => 5000, 'created_at' => '2026-07-01 10:00:00']);
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'amount_cents' => 3000, 'created_at' => '2026-07-01 11:00:00']);
        $this->createBoleto(['metadata' => ['tipo' => 'ISS'],  'amount_cents' => 8000, 'created_at' => '2026-07-01 12:00:00']);

        $result = $this->service->byMetadata($this->tenant, '2026-07-01', '2026-07-31', 'tipo');

        $this->assertCount(2, $result);

        $iptu = collect($result)->firstWhere('value', 'IPTU');
        $iss  = collect($result)->firstWhere('value', 'ISS');

        $this->assertNotNull($iptu);
        $this->assertEquals(2, $iptu['count']);
        $this->assertEquals(8000, $iptu['amount_cents']);

        $this->assertNotNull($iss);
        $this->assertEquals(1, $iss['count']);
        $this->assertEquals(8000, $iss['amount_cents']);
    }

    public function test_by_metadata_counts_paid_separately(): void
    {
        $this->createBoleto([
            'metadata'          => ['tipo' => 'IPTU'],
            'status'            => BoletoStatus::Paid,
            'paid_at'           => '2026-07-01 11:00:00',
            'paid_amount_cents' => 4900,
            'amount_cents'      => 5000,
            'created_at'        => '2026-07-01 10:00:00',
        ]);
        $this->createBoleto([
            'metadata'     => ['tipo' => 'IPTU'],
            'amount_cents' => 5000,
            'created_at'   => '2026-07-01 12:00:00',
        ]);

        $result = $this->service->byMetadata($this->tenant, '2026-07-01', '2026-07-31', 'tipo');

        $this->assertCount(1, $result);
        $this->assertEquals(2,    $result[0]['count']);
        $this->assertEquals(1,    $result[0]['paid_count']);
        $this->assertEquals(4900, $result[0]['paid_amount_cents']);
    }

    public function test_by_metadata_excludes_boletos_without_the_key(): void
    {
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'created_at' => '2026-07-01 10:00:00']);
        $this->createBoleto(['metadata' => ['outro' => 'valor'], 'created_at' => '2026-07-01 11:00:00']);
        $this->createBoleto(['metadata' => [], 'created_at' => '2026-07-01 12:00:00']);

        $result = $this->service->byMetadata($this->tenant, '2026-07-01', '2026-07-31', 'tipo');

        $this->assertCount(1, $result);
        $this->assertEquals('IPTU', $result[0]['value']);
        $this->assertEquals(1, $result[0]['count']);
    }

    public function test_by_metadata_returns_empty_when_no_matching_boletos(): void
    {
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'created_at' => '2026-07-01 10:00:00']);

        $result = $this->service->byMetadata($this->tenant, '2026-07-01', '2026-07-31', 'exercicio');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_by_metadata_filters_by_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name'                => 'Outro Meta',
            'document'            => '99887766000100',
            'email'               => 'outrometa@test.com',
            'status'              => TenantStatus::Active,
            'communication_model' => CommunicationModel::Email,
        ]);

        // 2 boletos do $this->tenant com tipo IPTU
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'created_at' => '2026-07-01 10:00:00']);
        $this->createBoleto(['metadata' => ['tipo' => 'IPTU'], 'created_at' => '2026-07-01 11:00:00']);

        // 1 boleto do outro tenant com tipo ISS (não deve aparecer)
        $other = Boleto::create([
            'tenant_id'        => $otherTenant->id,
            'boleto_config_id' => $this->config->id,
            'bank_partner_id'  => $this->config->bank_partner_id,
            'external_ref'     => 'META-OTHER-001',
            'status'           => BoletoStatus::Pending,
            'amount_cents'     => 10000,
            'due_date'         => now()->addDays(7)->toDateString(),
            'payer_name'       => 'Outro',
            'payer_document'   => '52998224725',
            'bank_boleto_id'   => 'META-OTHER',
            'digitable_line'   => '789',
            'dda_registered'   => true,
            'config_snapshot'  => [],
            'splits_snapshot'  => [],
            'metadata'         => ['tipo' => 'ISS'],
        ]);
        DB::table('boletos')->where('id', $other->id)->update(['created_at' => '2026-07-01 12:00:00']);

        $result = $this->service->byMetadata($this->tenant, '2026-07-01', '2026-07-31', 'tipo');

        $this->assertCount(1, $result);
        $this->assertEquals('IPTU', $result[0]['value']);
        $this->assertEquals(2, $result[0]['count']);
    }
}
