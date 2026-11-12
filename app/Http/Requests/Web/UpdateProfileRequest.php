<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'E-poçt ünvanı düzgün deyil.',
            'email.unique' => 'Bu e-poçt artıq istifadə olunur.',
            'name.max' => 'Ad 255 simvoldan uzun ola bilməz.',
            'surname.max' => 'Soyad 255 simvoldan uzun ola bilməz.',
        ];
    }
}
