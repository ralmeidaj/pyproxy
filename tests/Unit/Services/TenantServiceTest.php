<?php

namespace Tests\Unit\Services;

use App\DTOs\CreateTenantData;
use App\DTOs\UpdateTenantStatusData;
use App\Enums\CommunicationModel;
use App\Enums\TenantStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\BackofficeUser;
use App\Models\Tenant;
use App\Services\AuditLogService;
use App\Services\SanitizationService;
use App\Services\TenantService;
use Mockery;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    private SanitizationService $sanitization;
    private AuditLogService $auditLog;
    private TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitization = Mockery::mock(SanitizationService::class);
        $this->auditLog     = Mockery::mock(AuditLogService::class);
        $this->service      = new TenantService($this->sanitization, $this->auditLog);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --- create() ---

    public function test_create_throws_on_invalid_document(): void
    {
        $this->sanitization->shouldReceive('validateDocument')
            ->once()
            ->with('00000000000000')
            ->andReturn(null);

        $data = new CreateTenantData(
            name:               'Empresa X',
            document:           '00000000000000',
            email:              'x@x.com',
            phone:              null,
            communicationModel: CommunicationModel::Email,
            notes:              null,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CNPJ inválido.');

        $this->service->create($data);
    }

    public function test_create_validates_document_before_any_db_call(): void
    {
        $this->sanitization->shouldReceive('validateDocument')
            ->once()
            ->andReturn(null);

        $data = new CreateTenantData('X', 'bad', 'e@m.com', null, CommunicationModel::Email, null);

        try {
            $this->service->create($data);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        // Mockery verifies ->once() was satisfied at tearDown — no DB should have been called
        $this->auditLog->shouldNotHaveReceived('record');
    }

    // --- updateStatus() ---

    public function test_update_status_throws_on_invalid_transition_from_inactive(): void
    {
        $tenant = new Tenant();
        $tenant->forceFill(['id' => 1, 'status' => TenantStatus::Inactive]);

        $actor = new BackofficeUser();
        $actor->forceFill(['id' => 1, 'email' => 'admin@payproxy.com.br']);

        $data = new UpdateTenantStatusData(
            newStatus: TenantStatus::Active,
            reason:    'tentar reativar',
            actor:     $actor,
            ip:        '127.0.0.1',
        );

        $this->expectException(InvalidStatusTransitionException::class);

        $this->service->updateStatus($tenant, $data);
    }

    public function test_update_status_throws_on_invalid_transition_from_suspended_to_pending(): void
    {
        $tenant = new Tenant();
        $tenant->forceFill(['id' => 1, 'status' => TenantStatus::Suspended]);

        $actor = new BackofficeUser();
        $actor->forceFill(['id' => 1, 'email' => 'admin@payproxy.com.br']);

        $data = new UpdateTenantStatusData(
            newStatus: TenantStatus::PendingApproval,
            reason:    'transição inválida',
            actor:     $actor,
            ip:        '127.0.0.1',
        );

        $this->expectException(InvalidStatusTransitionException::class);

        $this->service->updateStatus($tenant, $data);
    }

    // --- TenantStatus enum logic ---

    public function test_pending_approval_can_transition_to_active(): void
    {
        $this->assertTrue(TenantStatus::PendingApproval->canTransitionTo(TenantStatus::Active));
    }

    public function test_pending_approval_can_transition_to_inactive(): void
    {
        $this->assertTrue(TenantStatus::PendingApproval->canTransitionTo(TenantStatus::Inactive));
    }

    public function test_pending_approval_cannot_transition_to_suspended(): void
    {
        $this->assertFalse(TenantStatus::PendingApproval->canTransitionTo(TenantStatus::Suspended));
    }

    public function test_active_can_transition_to_suspended(): void
    {
        $this->assertTrue(TenantStatus::Active->canTransitionTo(TenantStatus::Suspended));
    }

    public function test_active_can_transition_to_inactive(): void
    {
        $this->assertTrue(TenantStatus::Active->canTransitionTo(TenantStatus::Inactive));
    }

    public function test_active_cannot_transition_to_pending_approval(): void
    {
        $this->assertFalse(TenantStatus::Active->canTransitionTo(TenantStatus::PendingApproval));
    }

    public function test_suspended_can_transition_to_active(): void
    {
        $this->assertTrue(TenantStatus::Suspended->canTransitionTo(TenantStatus::Active));
    }

    public function test_suspended_can_transition_to_inactive(): void
    {
        $this->assertTrue(TenantStatus::Suspended->canTransitionTo(TenantStatus::Inactive));
    }

    public function test_inactive_has_no_allowed_transitions(): void
    {
        $this->assertEmpty(TenantStatus::Inactive->allowedTransitions());
    }

    public function test_status_labels_are_in_portuguese(): void
    {
        $this->assertSame('Pendente de Aprovação', TenantStatus::PendingApproval->label());
        $this->assertSame('Ativo',                 TenantStatus::Active->label());
        $this->assertSame('Suspenso',              TenantStatus::Suspended->label());
        $this->assertSame('Inativo',               TenantStatus::Inactive->label());
    }
}
