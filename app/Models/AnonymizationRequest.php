<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnonymizationRequest extends Model
{
    protected $fillable = [
        'cpf_hash',
        'boleto_ids',
        'status',
        'payer_email_masked',
        'boleto_count',
        'processed_at',
        'processed_by_label',
        'notes',
    ];

    protected $casts = [
        'boleto_ids'   => 'array',
        'processed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'  => 'Pendente',
            'done'     => 'Concluída',
            'rejected' => 'Rejeitada',
            default    => $this->status,
        };
    }
}
