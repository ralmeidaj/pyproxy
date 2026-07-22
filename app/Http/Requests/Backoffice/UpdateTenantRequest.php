<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\CommunicationModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name'                => ['required', 'string', 'max:255'],
            'document'            => ['required', 'string', Rule::unique('tenants', 'document')->ignore($tenant->id)],
            'email'               => ['required', 'email', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:20'],
            'communication_model' => ['required', Rule::enum(CommunicationModel::class)],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'allowed_ips'         => ['nullable', 'string', 'max:5000'],
            'email_entity_name'   => ['nullable', 'string', 'max:150'],
            'email_logo_url'      => ['nullable', 'url', 'max:2048'],
            'email_custom_text'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'O nome da empresa é obrigatório.',
            'document.required'            => 'O CNPJ é obrigatório.',
            'document.unique'              => 'Este CNPJ já está cadastrado.',
            'email.required'               => 'O e-mail é obrigatório.',
            'email.email'                  => 'Informe um e-mail válido.',
            'communication_model.required' => 'Selecione o modelo de comunicação.',
            'email_logo_url.url'           => 'Informe uma URL válida para o logo.',
        ];
    }
}
