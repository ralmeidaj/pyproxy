<?php

namespace App\Services;

use App\Enums\SplitType;
use App\Exceptions\InvalidSplitException;
use App\Models\BoletoConfig;
use Illuminate\Support\Collection;

class SplitService
{
    /**
     * Calcula e valida os splits para um dado valor de boleto.
     * Retorna array de splits com amount_cents calculado (RN-02, RN-08).
     *
     * @return array<int, array{name:string, bank_partner_payee_id:string, type:string, value:float|int, amount_cents:int}>
     */
    public function calculate(BoletoConfig $config, int $amountCents): array
    {
        $splits = $config->splitConfigs()->get();

        if ($splits->isEmpty()) {
            return [];
        }

        $this->validate($splits, $amountCents);

        return $splits->map(function ($split) use ($amountCents) {
            $amount = $split->type === SplitType::Percentage
                ? (int) round($amountCents * ($split->value / 100))
                : (int) round($split->value * 100); // valor em R$ → centavos

            return [
                'name'                   => $split->name,
                'bank_partner_payee_id'  => $split->bank_partner_payee_id,
                'payee_details'          => $split->payee_details,
                'type'                   => $split->type->value,
                'value'                  => (float) $split->value,
                'amount_cents'           => $amount,
            ];
        })->all();
    }

    /**
     * Valida regras RF-12: soma percentual ≤ 100%, soma fixo ≤ amountCents, resultado positivo.
     */
    public function validate(Collection $splits, int $amountCents): void
    {
        $totalPercentage = $splits
            ->where('type', SplitType::Percentage)
            ->sum('value');

        if ($totalPercentage > 100) {
            throw new InvalidSplitException('A soma dos percentuais de split ultrapassa 100%.');
        }

        $totalFixed = $splits
            ->where('type', SplitType::FixedAmount)
            ->sum(fn ($s) => (int) round($s->value * 100)); // valor em R$ → centavos

        if ($totalFixed > $amountCents) {
            throw new InvalidSplitException('A soma dos splits de valor fixo ultrapassa o valor do boleto.');
        }

        // Verifica que o valor residual (principal) é positivo
        $totalAllocated = (int) round($amountCents * ($totalPercentage / 100)) + $totalFixed; // $totalFixed já em centavos
        if ($totalAllocated > $amountCents) {
            throw new InvalidSplitException('A configuração de splits resulta em valor negativo para o favorecido principal.');
        }
    }
}
