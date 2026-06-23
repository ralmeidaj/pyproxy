<?php

namespace App\Models;

use App\Services\CryptoService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TenantUser extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'tenant_users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'active',
        'invite_token',
        'invite_expires_at',
        'totp_secret',
        'totp_enabled',
        'totp_confirmed_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
        'invite_token',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'active'            => 'boolean',
            'totp_enabled'      => 'boolean',
            'totp_confirmed_at' => 'datetime',
            'invite_expires_at' => 'datetime',
            'last_login_at'     => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasTotpEnabled(): bool
    {
        return $this->totp_enabled && ! is_null($this->totp_secret);
    }

    public function setEncryptedTotpSecret(string $secret): void
    {
        $this->totp_secret = app(CryptoService::class)->encrypt($secret);
    }

    public function getDecryptedTotpSecret(): ?string
    {
        return $this->totp_secret
            ? app(CryptoService::class)->decrypt($this->totp_secret)
            : null;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function canWrite(): bool
    {
        return in_array($this->role, ['admin', 'operator'], true);
    }
}
