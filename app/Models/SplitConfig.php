<?php

namespace App\Models;

use App\Enums\SplitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SplitConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'boleto_config_id',
        'name',
        'bank_partner_payee_id',
        'payee_details',
        'type',
        'value',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'type'          => SplitType::class,
            'value'         => 'decimal:4',
            'payee_details' => 'array',
        ];
    }

    public function boletoConfig(): BelongsTo
    {
        return $this->belongsTo(BoletoConfig::class);
    }
}
