<?php

namespace App\Http\Requests\Backoffice;

use Illuminate\Foundation\Http\FormRequest;

class CreateBoletoConfigRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'bank_partner_id'            => ['required', 'integer', 'exists:bank_partners,id'],
            'name'                       => ['required', 'string', 'max:100'],
            'is_default'                 => ['boolean'],
            'credential_api_key'         => ['required', 'string', 'max:255'],
            'credential_chave'           => ['required', 'string', 'max:255'],
            'prazo_vencimento_dias'      => ['nullable', 'integer', 'min:1', 'max:365'],
            'multa_percentual'           => ['nullable', 'numeric', 'min:0', 'max:10'],
            'juros_percentual_mes'       => ['nullable', 'numeric', 'min:0', 'max:5'],
            'desconto_percentual'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'desconto_antecedencia_dias' => ['nullable', 'integer', 'min:1'],
            'instrucoes'                 => ['nullable', 'array', 'max:2'],
            'instrucoes.*'               => ['string', 'max:255'],
            'webhook_url'                => ['nullable', 'url'],
            'webhook_secret'             => ['nullable', 'string', 'min:16', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_partner_id.required'    => 'Selecione o parceiro bancário.',
            'bank_partner_id.exists'      => 'Parceiro bancário inválido.',
            'name.required'               => 'O nome da configuração é obrigatório.',
            'credential_api_key.required' => 'A credencial (API Key) do PJBank é obrigatória.',
            'credential_chave.required'   => 'A chave (Chave) do PJBank é obrigatória.',
            'webhook_url.url'             => 'Informe uma URL de webhook válida.',
            'webhook_secret.min'          => 'O segredo do webhook deve ter pelo menos 16 caracteres.',
        ];
    }
}
