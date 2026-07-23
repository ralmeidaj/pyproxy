<?php

namespace App\Services;

use App\Models\Boleto;
use App\Models\ContribuinteAccessToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContribuinteService
{
    public function __construct(
        private readonly CryptoService $crypto,
    ) {}

    public function findEmailByCpf(string $cpf): ?string
    {
        $digits = preg_replace('/\D/', '', $cpf);

        return DB::table('boletos')
            ->whereNull('deleted_at')
            ->whereNotNull('payer_email')
            ->whereRaw("regexp_replace(payer_document, '[^0-9]', '', 'g') = ?", [$digits])
            ->orderByDesc('created_at')
            ->value('payer_email');
    }

    public function getBoletos(ContribuinteAccessToken $token): Collection
    {
        $digits = $this->crypto->decrypt($token->cpf_encrypted);

        return Boleto::with('tenant', 'splits')
            ->whereNull('boletos.deleted_at')
            ->whereRaw("regexp_replace(payer_document, '[^0-9]', '', 'g') = ?", [$digits])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPersonalData(ContribuinteAccessToken $token): array
    {
        $boletos = $this->getBoletos($token);
        $latest  = $boletos->first();

        return [
            'payer_name'    => $latest?->payer_name,
            'payer_email'   => $latest?->payer_email,
            'payer_phone'   => $latest?->payer_phone,
            'payer_address' => $latest?->payer_address,
            'boleto_count'  => $boletos->count(),
            'boletos'       => $boletos->map(fn ($b) => [
                'id'           => $b->id,
                'external_ref' => $b->external_ref,
                'amount_cents' => $b->amount_cents,
                'due_date'     => $b->due_date?->format('Y-m-d'),
                'status'       => $b->status instanceof \BackedEnum ? $b->status->value : $b->status,
                'status_label' => $b->status instanceof \App\Enums\BoletoStatus ? $b->status->label() : (string) $b->status,
                'created_at'   => $b->created_at?->toIso8601String(),
                'tenant_name'  => $b->tenant?->name,
            ])->values()->all(),
        ];
    }

    public function getBoletoIdsByToken(ContribuinteAccessToken $token): array
    {
        $digits = $this->crypto->decrypt($token->cpf_encrypted);

        return DB::table('boletos')
            ->whereNull('deleted_at')
            ->whereRaw("regexp_replace(payer_document, '[^0-9]', '', 'g') = ?", [$digits])
            ->pluck('id')
            ->all();
    }
}
