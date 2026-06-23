<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TotpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O código TOTP é obrigatório.',
            'code.size'     => 'O código deve ter exatamente 6 dígitos.',
            'code.regex'    => 'O código deve conter apenas dígitos.',
        ];
    }
}
