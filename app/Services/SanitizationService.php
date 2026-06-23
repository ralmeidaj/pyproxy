<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SanitizationService
{
    /**
     * Valida CPF (formato + dígitos verificadores da Receita Federal).
     * Retorna o CPF limpo (apenas dígitos) se válido, null caso contrário.
     */
    public function validateCpf(string $cpf): ?string
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return null;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $digit = (($sum * 10) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return null;
            }
        }

        return $cpf;
    }

    /**
     * Valida CNPJ (formato + dígitos verificadores).
     * Retorna o CNPJ limpo (apenas dígitos) se válido, null caso contrário.
     */
    public function validateCnpj(string $cnpj): ?string
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return null;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        foreach ([$weights1, $weights2] as $idx => $weights) {
            $sum = 0;
            foreach ($weights as $pos => $weight) {
                $sum += (int) $cnpj[$pos] * $weight;
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
            if ((int) $cnpj[12 + $idx] !== $digit) {
                return null;
            }
        }

        return $cnpj;
    }

    /**
     * Valida CPF ou CNPJ automaticamente pelo comprimento.
     */
    public function validateDocument(string $document): ?string
    {
        $digits = preg_replace('/\D/', '', $document);

        return match (strlen($digits)) {
            11 => $this->validateCpf($digits),
            14 => $this->validateCnpj($digits),
            default => null,
        };
    }

    /**
     * Consulta o CEP via ViaCEP e retorna os dados de endereço padronizados.
     * Retorna null se o CEP não existir ou a API estiver indisponível.
     *
     * @return array{cep: string, logradouro: string, complemento: string, bairro: string, localidade: string, uf: string}|null
     */
    public function lookupCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (isset($data['erro']) && $data['erro'] === true) {
            return null;
        }

        return [
            'cep'         => $data['cep'] ?? $cep,
            'logradouro'  => $this->capitalizeAddress($data['logradouro'] ?? ''),
            'complemento' => $data['complemento'] ?? '',
            'bairro'      => $this->capitalizeAddress($data['bairro'] ?? ''),
            'localidade'  => mb_strtoupper($data['localidade'] ?? ''),
            'uf'          => mb_strtoupper($data['uf'] ?? ''),
        ];
    }

    /**
     * Formata CEP com máscara (00000-000).
     */
    public function formatCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);

        return strlen($cep) === 8
            ? substr($cep, 0, 5) . '-' . substr($cep, 5)
            : $cep;
    }

    /**
     * Capitaliza corretamente um endereço (cada palavra, exceto preposições).
     */
    private function capitalizeAddress(string $value): string
    {
        $prepositions = ['de', 'da', 'do', 'das', 'dos', 'e', 'a', 'o', 'em', 'na', 'no'];

        $words = explode(' ', mb_strtolower(trim($value)));

        return implode(' ', array_map(
            fn ($word, $i) => ($i === 0 || ! in_array($word, $prepositions, true))
                ? mb_ucfirst($word)
                : $word,
            $words,
            array_keys($words),
        ));
    }
}
