<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IssueBoletoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Identificação
            'pedido_numero'       => ['required', 'string', 'max:100'],
            'valor'               => ['required', 'numeric', 'min:0.01'],
            'vencimento'          => ['required', 'date_format:m/d/Y'],

            // Pagador
            'nome_cliente'        => ['required', 'string', 'max:255'],
            'cpf_cliente'         => ['required', 'string', 'min:11', 'max:14'],
            'email_cliente'       => ['nullable', 'email'],
            'telefone_cliente'    => ['nullable', 'string', 'max:20'],

            // Endereço do pagador (obrigatório pela PJBank)
            'endereco_cliente'    => ['required', 'string', 'max:255'],
            'numero_cliente'      => ['required', 'string', 'max:20'],
            'complemento_cliente' => ['nullable', 'string', 'max:100'],
            'bairro_cliente'      => ['required', 'string', 'max:100'],
            'cidade_cliente'      => ['required', 'string', 'max:100'],
            'estado_cliente'      => ['required', 'string', 'size:2'],
            'cep_cliente'         => ['required', 'string', 'max:9'],

            // Opcionais
            'pix'                 => ['nullable', 'string', 'in:pix,pix-e-boleto'],
            'texto'               => ['nullable', 'string', 'max:500'],
            'logo_url'            => ['nullable', 'url'],
            'metadata'            => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'pedido_numero.required'    => 'O número do pedido é obrigatório.',
            'valor.required'            => 'O valor do boleto é obrigatório.',
            'valor.min'                 => 'O valor mínimo do boleto é R$ 0,01.',
            'vencimento.required'       => 'A data de vencimento é obrigatória.',
            'vencimento.date_format'    => 'A data de vencimento deve estar no formato MM/DD/AAAA (ex: 07/31/2026).',
            'nome_cliente.required'     => 'O nome do pagador é obrigatório.',
            'cpf_cliente.required'      => 'O CPF/CNPJ do pagador é obrigatório.',
            'endereco_cliente.required' => 'O logradouro do pagador é obrigatório.',
            'numero_cliente.required'   => 'O número do imóvel é obrigatório.',
            'bairro_cliente.required'   => 'O bairro é obrigatório.',
            'cidade_cliente.required'   => 'A cidade é obrigatória.',
            'estado_cliente.required'   => 'O estado (UF) é obrigatório.',
            'estado_cliente.size'       => 'O estado deve ter 2 caracteres (ex: BA).',
            'cep_cliente.required'      => 'O CEP é obrigatório.',
        ];
    }
}
