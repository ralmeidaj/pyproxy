<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyUsageDaily extends Model
{
    protected $table = 'api_key_usage_daily';

    protected $fillable = [
        'api_key_id',
        'date',
        'count',
        'alert_80_sent',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'alert_80_sent' => 'boolean',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
