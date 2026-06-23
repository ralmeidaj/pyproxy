<?php

namespace App\Models;

use App\Services\CryptoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class BackofficeUser extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'backoffice_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'totp_secret',
        'totp_enabled',
        'totp_confirmed_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'totp_enabled'      => 'boolean',
            'totp_confirmed_at' => 'datetime',
            'last_login_at'     => 'datetime',
        ];
    }

    public function hasTotpEnabled(): bool
    {
        return $this->totp_enabled && ! is_null($this->totp_secret);
    }

    public function setEncryptedTotpSecret(string $secret): void
    {
        $crypto = app(CryptoService::class);

        $this->totp_secret = $crypto->encrypt($secret);
    }

    public function getDecryptedTotpSecret(): ?string
    {
        if (is_null($this->totp_secret)) {
            return null;
        }

        return app(CryptoService::class)->decrypt($this->totp_secret);
    }
}
