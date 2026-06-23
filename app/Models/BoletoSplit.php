<?php

namespace App\Models;

use App\Enums\SplitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletoSplit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'boleto_id',
        'name',
        'bank_partner_payee_id',
        'type',
        'value',
        'amount_cents',
    ];

    protected function casts(): array
    {
        return [
            'type'       => SplitType::class,
            'value'      => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function boleto(): BelongsTo
    {
        return $this->belongsTo(Boleto::class);
    }
}
