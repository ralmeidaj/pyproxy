<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'boleto_id',
        'url',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'next_attempt_at',
        'response_status',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'payload'         => 'array',
            'last_attempt_at' => 'datetime',
            'next_attempt_at' => 'datetime',
        ];
    }

    public function boleto(): BelongsTo
    {
        return $this->belongsTo(Boleto::class);
    }
}
