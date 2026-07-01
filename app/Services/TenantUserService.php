<?php

namespace App\Services;

use App\DTOs\InviteTenantUserData;
use App\Mail\TenantUserInviteMail;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Rules\PasswordPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TenantUserService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Tenant $tenant): Collection
    {
        return TenantUser::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function invite(Tenant $tenant, InviteTenantUserData $data, TenantUser $actor, ?string $ip = null): TenantUser
    {
        $raw = bin2hex(random_bytes(32));

        $user = DB::transaction(function () use ($tenant, $data, $raw): TenantUser {
            $user = TenantUser::create([
                'tenant_id'         => $tenant->id,
                'name'              => $data->name,
                'email'             => $data->email,
                'role'              => $data->role,
                'active'            => false,
                'invite_token'      => hash('sha256', $raw),
                'invite_expires_at' => now()->addHours(48),
            ]);

            Mail::to($data->email)->send(new TenantUserInviteMail($user, $raw, $tenant));

            return $user;
        });

        $this->auditLog->record(
            action:       'tenant_user.invited',
            resourceType: 'TenantUser',
            resourceId:   $user->id,
            actorType:    'tenant_user',
            actorId:      $actor->id,
            actorLabel:   $actor->email,
            tenantId:     $tenant->id,
            payload:      ['email' => $data->email, 'role' => $data->role],
            ip:           $ip,
        );

        return $user;
    }

    public function resendInvite(TenantUser $user, TenantUser $actor, ?string $ip = null): void
    {
        $raw = bin2hex(random_bytes(32));

        $user->update([
            'invite_token'      => hash('sha256', $raw),
            'invite_expires_at' => now()->addHours(48),
        ]);

        $user->load('tenant');
        Mail::to($user->email)->send(new TenantUserInviteMail($user, $raw, $user->tenant));

        $this->auditLog->record(
            action:       'tenant_user.invite_resent',
            resourceType: 'TenantUser',
            resourceId:   $user->id,
            actorType:    'tenant_user',
            actorId:      $actor->id,
            actorLabel:   $actor->email,
            tenantId:     $user->tenant_id,
            payload:      ['email' => $user->email],
            ip:           $ip,
        );
    }

    public function acceptInvite(string $rawToken, string $password): TenantUser
    {
        $hashed = hash('sha256', $rawToken);

        $user = TenantUser::where('invite_token', $hashed)
            ->where('invite_expires_at', '>', now())
            ->firstOrFail();

        DB::transaction(function () use ($user, $password): void {
            $user->update([
                'password'            => $password,
                'active'              => true,
                'invite_token'        => null,
                'invite_expires_at'   => null,
                'password_changed_at' => now(),
            ]);
        });

        $this->auditLog->record(
            action:       'tenant_user.activated',
            resourceType: 'TenantUser',
            resourceId:   $user->id,
            actorType:    'tenant_user',
            actorId:      $user->id,
            actorLabel:   $user->email,
            tenantId:     $user->tenant_id,
            payload:      ['email' => $user->email],
        );

        return $user->fresh();
    }

    public function toggleActive(TenantUser $user, bool $active, TenantUser $actor, ?string $ip = null): void
    {
        $user->update(['active' => $active]);

        $this->auditLog->record(
            action:       $active ? 'tenant_user.activated' : 'tenant_user.deactivated',
            resourceType: 'TenantUser',
            resourceId:   $user->id,
            actorType:    'tenant_user',
            actorId:      $actor->id,
            actorLabel:   $actor->email,
            tenantId:     $user->tenant_id,
            payload:      ['email' => $user->email, 'active' => $active],
            ip:           $ip,
        );
    }
}
