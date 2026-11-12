<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Adınızı daxil edin.',
            'phone.required' => 'Telefon nömrənizi daxil edin.',
            'email.email' => 'E-poçt ünvanı düzgün deyil.',
            'email.unique' => 'Bu e-poçt ünvanı artıq istifadə olunur.',
            'password.required' => 'Şifrəni daxil edin.',
            'password.min' => 'Şifrə ən azı 6 simvol olmalıdır.',
            'password.confirmed' => 'Şifrələr uyğun gəlmir.',
        ];
    }
}
