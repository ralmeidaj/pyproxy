<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    protected $fillable = [
        'tenant_id',
        'requested_by_type',
        'requested_by_id',
        'format',
        'filters',
        'status',
        'row_count',
        'file_path',
        'download_url',
        'expires_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'filters'    => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
