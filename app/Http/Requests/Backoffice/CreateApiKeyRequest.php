<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:100'],
            'scopes'                 => ['required', 'array', 'min:1'],
            'scopes.*'               => ['required', Rule::in(['boleto:write', 'boleto:read', 'report:read'])],
            'rate_limit_per_minute'  => ['nullable', 'integer', 'min:1', 'max:600'],
            'daily_limit'            => ['nullable', 'integer', 'min:1'],
            'monthly_limit'          => ['nullable', 'integer', 'min:1'],
            'max_amount_cents'       => ['nullable', 'integer', 'min:1'],
            'allow_batch'            => ['nullable', 'boolean'],
            'allowed_metadata_types' => ['nullable', 'array'],
            'allowed_metadata_types.*' => ['string', 'max:50'],
            'expires_at'             => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'O nome da API key é obrigatório.',
            'scopes.required'  => 'Selecione ao menos um escopo.',
            'scopes.*.in'      => 'Escopo inválido.',
            'expires_at.after' => 'A data de expiração deve ser futura.',
        ];
    }
}
