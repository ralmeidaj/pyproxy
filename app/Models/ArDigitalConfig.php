<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArDigitalConfig extends Model
{
    use SoftDeletes;

    protected $table = 'ar_digital_configs';

    protected $fillable = [
        'tenant_id',
        'enabled',
        'pixel_tracking',
        'cpf_confirmation',
        'act_provider',
    ];

    protected function casts(): array
    {
        return [
            'enabled'          => 'boolean',
            'pixel_tracking'   => 'boolean',
            'cpf_confirmation' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
