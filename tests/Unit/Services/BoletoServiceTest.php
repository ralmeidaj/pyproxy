<?php

namespace Tests\Unit\Services;

use App\DTOs\BankBoletoResult;
use App\DTOs\IssueBoletoData;
use App\Enums\BoletoStatus;
use App\Exceptions\BoletoCannotBeCancelledException;
use App\Models\Boleto;
use App\Models\Tenant;
use App\Services\ArDigitalService;
use App\Services\AuditLogService;
use App\Services\BankPartners\BankPartnerFactory;
use App\Services\BoletoService;
use App\Services\SplitService;
use Tests\TestCase;

class BoletoServiceTest extends TestCase
{
    private BankPartnerFactory $factory;
    private SplitService       $splitService;
    private AuditLogService    $auditLog;
    private ArDigitalService   $arDigital;
    private BoletoService      $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory      = $this->createMock(BankPartnerFactory::class);
        $this->splitService = $this->createMock(SplitService::class);
        $this->auditLog     = $this->createMock(AuditLogService::class);
        $this->arDigital    = $this->createMock(ArDigitalService::class);

        $this->service = new BoletoService(
            $this->factory,
            $this->splitService,
            $this->auditLog,
            $this->arDigital,
        );
    }

    // --- cancel() ---

    public function test_cancel_throws_when_status_is_not_pending(): void
    {
        $boleto = new class extends Boleto {
            public BoletoStatus $status;
        };
        $boleto->status = BoletoStatus::Paid;

        $tenant = $this->createMock(Tenant::class);

        $this->expectException(BoletoCannotBeCancelledException::class);

        $this->service->cancel($boleto, $tenant);
    }

    // --- enums / business rules ---

    public function test_boleto_status_pending_can_cancel(): void
    {
        $this->assertTrue(BoletoStatus::Pending->canCancel());
    }

    public function test_boleto_status_paid_cannot_cancel(): void
    {
        $this->assertFalse(BoletoStatus::Paid->canCancel());
    }

    public function test_boleto_status_cancelled_cannot_cancel(): void
    {
        $this->assertFalse(BoletoStatus::Cancelled->canCancel());
    }

    public function test_boleto_status_expired_cannot_cancel(): void
    {
        $this->assertFalse(BoletoStatus::Expired->canCancel());
    }

    public function test_paid_is_final(): void
    {
        $this->assertTrue(BoletoStatus::Paid->isFinal());
    }

    public function test_cancelled_is_final(): void
    {
        $this->assertTrue(BoletoStatus::Cancelled->isFinal());
    }

    public function test_expired_is_final(): void
    {
        $this->assertTrue(BoletoStatus::Expired->isFinal());
    }

    public function test_pending_is_not_final(): void
    {
        $this->assertFalse(BoletoStatus::Pending->isFinal());
    }

    public function test_boleto_status_labels(): void
    {
        $this->assertSame('Pendente',  BoletoStatus::Pending->label());
        $this->assertSame('Pago',      BoletoStatus::Paid->label());
        $this->assertSame('Cancelado', BoletoStatus::Cancelled->label());
        $this->assertSame('Expirado',  BoletoStatus::Expired->label());
    }

    public function test_bank_boleto_result_holds_all_fields(): void
    {
        $result = new BankBoletoResult(
            bankBoletoId:  'NR-001',
            barcode:       '34191.12345',
            digitableLine: '34191.12345 12345.123456 12345.123456 1 12340000010000',
            pixQrCode:     'pix-qr-code-data',
            pdfUrl:        'https://example.com/boleto.pdf',
            ddaRegistered: true,
        );

        $this->assertSame('NR-001', $result->bankBoletoId);
        $this->assertSame('34191.12345', $result->barcode);
        $this->assertTrue($result->ddaRegistered);
    }

    // --- IssueBoletoData ---

    public function test_issue_boleto_data_dto_holds_values(): void
    {
        $data = new IssueBoletoData(
            externalRef:   'REF-001',
            amountCents:   10000,
            dueDate:       '2026-07-01',
            payerName:     'João Silva',
            payerDocument: '12345678000195',
            payerEmail:    'joao@example.com',
        );

        $this->assertSame('REF-001', $data->externalRef);
        $this->assertSame(10000, $data->amountCents);
        $this->assertSame('12345678000195', $data->payerDocument);
    }

    public function test_issue_boleto_data_defaults(): void
    {
        $data = new IssueBoletoData(
            externalRef:   'REF-002',
            amountCents:   5000,
            dueDate:       '2026-08-01',
            payerName:     'Maria',
            payerDocument: '00000000000191',
            payerEmail:    'maria@example.com',
        );

        $this->assertNull($data->payerPhone);
        $this->assertNull($data->payerAddress);
        $this->assertSame([], $data->metadata);
    }
}
