<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_number' => preg_replace('/\D+/', '', (string) $this->input('contact_number')),
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/'],
            'city' => ['required', 'string', 'max:255'],
            'town_village_district' => ['required', 'string', 'max:255'],
            'street_building_number' => ['required', 'string', 'max:255'],
            'unit_floor_apartment' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Ad və soyad mütləqdir.',
            'contact_number.required' => 'Əlaqə nömrəsi mütləqdir.',
            'contact_number.regex' => 'Əlaqə nömrəsi 9-15 rəqəmdən ibarət olmalıdır.',
            'city.required' => 'Şəhər seçilməlidir.',
            'town_village_district.required' => 'Rayon/qəsəbə mütləqdir.',
            'street_building_number.required' => 'Küçə və bina mütləqdir.',
            'unit_floor_apartment.required' => 'Mənzil/floor mütləqdir.',
        ];
    }
}
