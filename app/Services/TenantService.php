<?php

namespace App\Services;

use App\DTOs\CreateTenantData;
use App\DTOs\UpdateTenantStatusData;
use App\Enums\TenantStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Tenant;
use App\Models\TenantStatusHistory;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(
        private readonly SanitizationService $sanitization,
        private readonly AuditLogService     $auditLog,
    ) {}

    public function create(CreateTenantData $data): Tenant
    {
        $document = $this->sanitization->validateDocument($data->document);

        if (! $document) {
            throw new \InvalidArgumentException('CNPJ inválido.');
        }

        $tenant = DB::transaction(function () use ($data, $document): Tenant {
            $tenant = Tenant::create([
                'name'                => $data->name,
                'document'            => $document,
                'email'               => $data->email,
                'phone'               => $data->phone,
                'status'              => TenantStatus::PendingApproval,
                'communication_model' => $data->communicationModel,
                'notes'               => $data->notes,
            ]);

            return $tenant;
        });

        return $tenant;
    }

    public function updateStatus(Tenant $tenant, UpdateTenantStatusData $data): Tenant
    {
        if (! $tenant->status->canTransitionTo($data->newStatus)) {
            throw new InvalidStatusTransitionException(
                "Transição de {$tenant->status->label()} para {$data->newStatus->label()} não é permitida."
            );
        }

        DB::transaction(function () use ($tenant, $data): void {
            $fromStatus = $tenant->status;

            $tenant->update(['status' => $data->newStatus]);

            TenantStatusHistory::create([
                'tenant_id'          => $tenant->id,
                'backoffice_user_id' => $data->actor->id,
                'from_status'        => $fromStatus,
                'to_status'          => $data->newStatus,
                'reason'             => $data->reason,
                'ip'                 => $data->ip,
                'created_at'         => now(),
            ]);

            $this->auditLog->record(
                action:       'tenant.status_changed',
                resourceType: 'Tenant',
                resourceId:   $tenant->id,
                actorType:    'backoffice_user',
                actorId:      $data->actor->id,
                actorLabel:   $data->actor->email,
                tenantId:     $tenant->id,
                payload:      [
                    'from' => $fromStatus->value,
                    'to'   => $data->newStatus->value,
                ],
                ip: $data->ip,
            );
        });

        return $tenant->fresh();
    }

    public function paginate(int $perPage = 20, ?string $search = null, ?TenantStatus $status = null)
    {
        return Tenant::query()
            ->when($search, fn ($q) => $q->where(fn ($q) =>
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
            ))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
