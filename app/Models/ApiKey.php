<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiKey extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'key_prefix',
        'key_hash',
        'scopes',
        'rate_limit_per_minute',
        'daily_limit',
        'monthly_limit',
        'max_amount_cents',
        'allow_batch',
        'allowed_metadata_types',
        'expires_at',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'scopes'                 => 'array',
            'allowed_metadata_types' => 'array',
            'allow_batch'            => 'boolean',
            'expires_at'             => 'datetime',
            'revoked_at'             => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(BackofficeUser::class, 'revoked_by');
    }

    public function usageDaily(): HasMany
    {
        return $this->hasMany(ApiKeyUsageDaily::class);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at)
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function isExpiringSoon(int $days = 15): bool
    {
        return $this->isActive()
            && $this->expires_at !== null
            && $this->expires_at->isBefore(now()->addDays($days));
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
