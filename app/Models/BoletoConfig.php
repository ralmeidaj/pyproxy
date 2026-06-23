<?php

namespace App\Models;

use App\Services\CryptoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoletoConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'bank_partner_id',
        'name',
        'is_default',
        'credentials_encrypted',
        'prazo_vencimento_dias',
        'multa_percentual',
        'juros_percentual_mes',
        'desconto_percentual',
        'desconto_antecedencia_dias',
        'instrucoes',
        'webhook_url',
        'webhook_secret_encrypted',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'instrucoes'  => 'array',
            'multa_percentual'       => 'decimal:2',
            'juros_percentual_mes'   => 'decimal:2',
            'desconto_percentual'    => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bankPartner(): BelongsTo
    {
        return $this->belongsTo(BankPartner::class);
    }

    public function splitConfigs(): HasMany
    {
        return $this->hasMany(SplitConfig::class)->orderBy('priority');
    }

    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class);
    }

    public function getCredentials(): array
    {
        return json_decode(app(CryptoService::class)->decrypt($this->credentials_encrypted), true);
    }

    public function getWebhookSecret(): ?string
    {
        if (! $this->webhook_secret_encrypted) {
            return null;
        }
        return app(CryptoService::class)->decrypt($this->webhook_secret_encrypted);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
