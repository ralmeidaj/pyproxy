<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class StoreSplitConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                            => ['required', 'string', 'max:100'],
            'type'                            => ['required', 'in:percentage,fixed_amount'],
            'value'                           => ['required', 'numeric', 'min:0.01'],
            'priority'                        => ['integer', 'min:0', 'max:999'],
            'payee_details'                   => ['required', 'array'],
            'payee_details.nome'              => ['required', 'string', 'max:255'],
            'payee_details.cnpj'              => ['required', 'string', 'max:18'],
            'payee_details.banco_repasse'     => ['required', 'string', 'max:10'],
            'payee_details.agencia_repasse'   => ['required', 'string', 'max:20'],
            'payee_details.conta_repasse'     => ['required', 'string', 'max:20'],
            'payee_details.porcentagem_encargos' => ['numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                          => 'O nome do favorecido é obrigatório.',
            'type.required'                          => 'O tipo de split é obrigatório.',
            'value.required'                         => 'O valor do split é obrigatório.',
            'payee_details.nome.required'            => 'O nome do favorecido é obrigatório.',
            'payee_details.cnpj.required'            => 'O CNPJ do favorecido é obrigatório.',
            'payee_details.banco_repasse.required'   => 'O banco de repasse é obrigatório.',
            'payee_details.agencia_repasse.required' => 'A agência de repasse é obrigatória.',
            'payee_details.conta_repasse.required'   => 'A conta de repasse é obrigatória.',
        ];
    }
}
