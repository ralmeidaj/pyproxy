<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class InviteTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tenant_users,email'],
            'role'  => ['required', 'in:admin,operator,viewer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O nome é obrigatório.',
            'name.max'       => 'O nome deve ter no máximo 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'email.unique'   => 'Este e-mail já está cadastrado na plataforma.',
            'role.required'  => 'O perfil é obrigatório.',
            'role.in'        => 'Perfil inválido. Use: admin, operator ou viewer.',
        ];
    }
}
