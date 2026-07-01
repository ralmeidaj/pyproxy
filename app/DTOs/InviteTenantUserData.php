<?php

namespace App\DTOs;

use App\Http\Requests\Portal\InviteTenantUserRequest;

final readonly class InviteTenantUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
    ) {}

    public static function fromRequest(InviteTenantUserRequest $request): self
    {
        return new self(
            name:  $request->name,
            email: $request->email,
            role:  $request->role,
        );
    }
}
