<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArDigitalTimestamp extends Model
{
    protected $table = 'ar_digital_timestamps';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'hash_input',
        'tsr_base64',
        'act_provider',
        'verificado_em',
    ];

    protected function casts(): array
    {
        return [
            'verificado_em' => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ArDigitalEvent::class, 'event_id');
    }
}
