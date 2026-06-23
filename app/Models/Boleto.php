<?php

namespace App\Models;

use App\Enums\BoletoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boleto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'boleto_config_id',
        'bank_partner_id',
        'external_ref',
        'status',
        'amount_cents',
        'due_date',
        'payer_name',
        'payer_document',
        'payer_email',
        'payer_phone',
        'payer_address',
        'bank_boleto_id',
        'token_facilitador',
        'barcode',
        'digitable_line',
        'pix_qr_code',
        'pdf_url',
        'dda_registered',
        'paid_at',
        'paid_amount_cents',
        'paid_channel',
        'cancelled_at',
        'config_snapshot',
        'splits_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status'         => BoletoStatus::class,
            'due_date'       => 'date',
            'payer_address'  => 'array',
            'config_snapshot' => 'array',
            'splits_snapshot' => 'array',
            'metadata'       => 'array',
            'dda_registered' => 'boolean',
            'paid_at'        => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function boletoConfig(): BelongsTo
    {
        return $this->belongsTo(BoletoConfig::class);
    }

    public function bankPartner(): BelongsTo
    {
        return $this->belongsTo(BankPartner::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(BoletoSplit::class);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
