<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Telefon və ya e-poçt ünvanını daxil edin.',
            'password.required' => 'Şifrəni daxil edin.',
            'password.min' => 'Şifrə ən azı 6 simvol olmalıdır.',
        ];
    }
}
