<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Mağaza adı mütləqdir.',
            'name.max' => 'Mağaza adı 120 simvoldan uzun ola bilməz.',
            'logo.image' => 'Loqo şəkil olmalıdır.',
            'logo.mimes' => 'Loqo üçün yalnız JPG, PNG və WEBP qəbul edilir.',
            'logo.max' => 'Loqo maksimum 4MB olmalıdır.',
        ];
    }
}
