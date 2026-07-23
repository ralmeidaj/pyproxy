<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contribuinte extends Model
{
    protected $fillable = ['cpf_hash'];

    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class);
    }

    public static function firstOrCreateByCpf(string $cpf): self
    {
        $hash = hash('sha256', preg_replace('/\D/', '', $cpf));
        return static::firstOrCreate(['cpf_hash' => $hash]);
    }
}
