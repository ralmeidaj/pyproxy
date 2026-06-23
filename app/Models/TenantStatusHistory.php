<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStatusHistory extends Model
{
    protected $table = 'tenant_status_history';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'backoffice_user_id',
        'from_status',
        'to_status',
        'reason',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => TenantStatus::class,
            'to_status'   => TenantStatus::class,
            'created_at'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function backofficeUser(): BelongsTo
    {
        return $this->belongsTo(BackofficeUser::class);
    }
}
