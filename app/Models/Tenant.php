<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// Models referenced via HasMany — resolved at runtime

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'status',
        'communication_model',
        'notes',
        'email_entity_name',
        'email_logo_url',
        'email_custom_text',
    ];

    protected function casts(): array
    {
        return [
            'status'              => \App\Enums\TenantStatus::class,
            'communication_model' => \App\Enums\CommunicationModel::class,
        ];
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TenantStatusHistory::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function activeApiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function boletoConfigs(): HasMany
    {
        return $this->hasMany(BoletoConfig::class);
    }

    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class);
    }

    public function isActive(): bool
    {
        return $this->status === \App\Enums\TenantStatus::Active;
    }
}
