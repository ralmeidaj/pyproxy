<?php

namespace App\Models;

use App\Enums\BankPartnerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankPartner extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'features',
        'base_url',
    ];

    protected function casts(): array
    {
        return [
            'type'     => BankPartnerType::class,
            'features' => 'array',
        ];
    }

    public function boletoConfigs(): HasMany
    {
        return $this->hasMany(BoletoConfig::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function supports(string $feature): bool
    {
        $features = $this->features ?? [];
        // Suporta formato objeto {"split":true} e formato lista ["split"]
        if (array_is_list($features)) {
            return in_array($feature, $features, true);
        }
        return !empty($features[$feature]);
    }
}
