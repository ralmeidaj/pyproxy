<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArDigitalNotification extends Model
{
    use SoftDeletes;

    protected $table = 'ar_digital_notifications';

    protected $fillable = [
        'boleto_id',
        'tenant_id',
        'destinatario_email',
        'destinatario_whatsapp',
        'meta_whatsapp_message_id',
        'cpf_hash',
        'hash_documento',
        'token',
        'status',
        'laudo_path',
    ];

    public function boleto(): BelongsTo
    {
        return $this->belongsTo(Boleto::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ArDigitalEvent::class, 'notification_id');
    }
}
