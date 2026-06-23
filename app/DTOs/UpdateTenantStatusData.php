<?php

namespace App\DTOs;

use App\Enums\TenantStatus;
use App\Http\Requests\Backoffice\UpdateTenantStatusRequest;
use App\Models\BackofficeUser;

final readonly class UpdateTenantStatusData
{
    public function __construct(
        public TenantStatus   $newStatus,
        public string         $reason,
        public BackofficeUser $actor,
        public ?string        $ip,
    ) {}

    public static function fromRequest(UpdateTenantStatusRequest $request, BackofficeUser $actor): self
    {
        return new self(
            newStatus: TenantStatus::from($request->status),
            reason:    $request->reason,
            actor:     $actor,
            ip:        $request->ip(),
        );
    }
}
