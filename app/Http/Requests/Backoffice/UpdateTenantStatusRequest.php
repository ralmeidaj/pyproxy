<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O novo status é obrigatório.',
            'reason.required' => 'O motivo é obrigatório.',
            'reason.min'      => 'O motivo deve ter ao menos 10 caracteres.',
        ];
    }
}
