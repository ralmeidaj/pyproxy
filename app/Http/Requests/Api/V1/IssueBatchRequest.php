<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IssueBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'external_ref'                   => ['required', 'string', 'max:100'],

            'boletos'                         => ['required', 'array', 'min:1', 'max:500'],

            // Identificação de cada item
            'boletos.*.pedido_numero'         => ['required', 'string', 'max:100'],
            'boletos.*.valor'                 => ['required', 'numeric', 'min:0.01'],
            'boletos.*.vencimento'            => ['required', 'date_format:m/d/Y'],

            // Pagador
            'boletos.*.nome_cliente'          => ['required', 'string', 'max:255'],
            'boletos.*.cpf_cliente'           => ['required', 'string', 'min:11', 'max:14'],
            'boletos.*.email_cliente'         => ['nullable', 'email'],
            'boletos.*.telefone_cliente'      => ['nullable', 'string', 'max:20'],

            // Endereço
            'boletos.*.endereco_cliente'      => ['required', 'string', 'max:255'],
            'boletos.*.numero_cliente'        => ['required', 'string', 'max:20'],
            'boletos.*.complemento_cliente'   => ['nullable', 'string', 'max:100'],
            'boletos.*.bairro_cliente'        => ['required', 'string', 'max:100'],
            'boletos.*.cidade_cliente'        => ['required', 'string', 'max:100'],
            'boletos.*.estado_cliente'        => ['required', 'string', 'size:2'],
            'boletos.*.cep_cliente'           => ['required', 'string', 'max:9'],

            // Opcionais
            'boletos.*.pix'                   => ['nullable', 'string', 'in:pix,pix-e-boleto'],
            'boletos.*.texto'                 => ['nullable', 'string', 'max:500'],
            'boletos.*.logo_url'              => ['nullable', 'url'],
            'boletos.*.metadata'              => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'external_ref.required'                => 'A referência externa do lote é obrigatória.',
            'boletos.required'                     => 'A lista de boletos é obrigatória.',
            'boletos.min'                          => 'O lote deve conter ao menos 1 boleto.',
            'boletos.max'                          => 'O lote pode conter no máximo 500 boletos.',
            'boletos.*.pedido_numero.required'     => 'O número do pedido é obrigatório (item :index).',
            'boletos.*.valor.required'             => 'O valor é obrigatório (item :index).',
            'boletos.*.valor.min'                  => 'O valor mínimo é R$ 0,01 (item :index).',
            'boletos.*.vencimento.required'        => 'A data de vencimento é obrigatória (item :index).',
            'boletos.*.vencimento.date_format'     => 'Data de vencimento deve estar no formato MM/DD/AAAA (item :index).',
            'boletos.*.nome_cliente.required'      => 'O nome do pagador é obrigatório (item :index).',
            'boletos.*.cpf_cliente.required'       => 'O CPF/CNPJ é obrigatório (item :index).',
            'boletos.*.endereco_cliente.required'  => 'O logradouro é obrigatório (item :index).',
            'boletos.*.numero_cliente.required'    => 'O número do imóvel é obrigatório (item :index).',
            'boletos.*.bairro_cliente.required'    => 'O bairro é obrigatório (item :index).',
            'boletos.*.cidade_cliente.required'    => 'A cidade é obrigatória (item :index).',
            'boletos.*.estado_cliente.required'    => 'O estado (UF) é obrigatório (item :index).',
            'boletos.*.estado_cliente.size'        => 'O estado deve ter 2 caracteres (item :index).',
            'boletos.*.cep_cliente.required'       => 'O CEP é obrigatório (item :index).',
        ];
    }
}
