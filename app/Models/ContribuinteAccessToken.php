<?php

namespace App\Models;

use App\Services\CryptoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContribuinteAccessToken extends Model
{
    protected $fillable = ['cpf_hash', 'cpf_encrypted', 'token', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        if ($this->used_at === null) {
            $this->update(['used_at' => now()]);
        }
    }

    public static function generate(string $cpf): self
    {
        $digits = preg_replace('/\D/', '', $cpf);

        return static::create([
            'cpf_hash'      => hash('sha256', $digits),
            'cpf_encrypted' => app(CryptoService::class)->encrypt($digits),
            'token'         => (string) Str::uuid(),
            'expires_at'    => now()->addHours(24),
        ]);
    }

    public static function findValid(string $token): ?self
    {
        return static::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }
}
