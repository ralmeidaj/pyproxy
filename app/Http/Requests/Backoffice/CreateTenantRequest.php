<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\CommunicationModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'document'            => ['required', 'string'],
            'email'               => ['required', 'email', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:20'],
            'communication_model' => ['required', Rule::enum(CommunicationModel::class)],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'O nome é obrigatório.',
            'document.required'            => 'O CNPJ é obrigatório.',
            'email.required'               => 'O e-mail é obrigatório.',
            'email.email'                  => 'Informe um e-mail válido.',
            'communication_model.required' => 'O modelo de comunicação é obrigatório.',
        ];
    }
}
