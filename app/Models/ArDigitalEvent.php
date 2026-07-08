<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArDigitalEvent extends Model
{
    protected $table = 'ar_digital_events';

    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'tipo',
        'canal',
        'ip',
        'user_agent',
        'geolocation',
        'smtp_code',
        'smtp_response',
        'tsr_path',
        'ocorrido_em',
    ];

    protected function casts(): array
    {
        return [
            'ocorrido_em' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(ArDigitalNotification::class, 'notification_id');
    }

    public function timestamp(): HasOne
    {
        return $this->hasOne(ArDigitalTimestamp::class, 'event_id');
    }
}
