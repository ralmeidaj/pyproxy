<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletosBatch extends Model
{
    protected $table = 'boletos_batches';

    protected $fillable = [
        'tenant_id',
        'api_key_id',
        'external_ref',
        'status',
        'total_count',
        'processed_count',
        'success_count',
        'error_count',
        'items',
        'results',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status'       => BatchStatus::class,
        'items'        => 'array',
        'results'      => 'array',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
