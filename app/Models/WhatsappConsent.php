<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappConsent extends Model
{
    protected $fillable = [
        'tenant_id',
        'cpf_hash',
        'phone_hash',
        'consent_text_version',
        'consented_at',
        'consent_ip',
        'revoked_at',
        'revocation_ip',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'revoked_at'   => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function hasActiveConsent(int $tenantId, string $document): bool
    {
        $cpfHash = hash('sha256', preg_replace('/\D/', '', $document));

        return static::where('tenant_id', $tenantId)
            ->where('cpf_hash', $cpfHash)
            ->whereNull('revoked_at')
            ->exists();
    }

    public static function grantConsent(int $tenantId, string $document, string $phone, string $ip): self
    {
        $cpfHash   = hash('sha256', preg_replace('/\D/', '', $document));
        $phoneHash = hash('sha256', preg_replace('/\D/', '', $phone));

        return static::updateOrCreate(
            ['tenant_id' => $tenantId, 'cpf_hash' => $cpfHash],
            [
                'phone_hash'           => $phoneHash,
                'consented_at'         => now(),
                'consent_ip'           => $ip,
                'revoked_at'           => null,
                'revocation_ip'        => null,
                'consent_text_version' => '1.0',
            ],
        );
    }
}
