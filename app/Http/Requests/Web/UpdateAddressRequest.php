<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('contact_number')) {
            $this->merge([
                'contact_number' => preg_replace('/\D+/', '', (string) $this->input('contact_number')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'town_village_district' => ['sometimes', 'required', 'string', 'max:255'],
            'street_building_number' => ['sometimes', 'required', 'string', 'max:255'],
            'unit_floor_apartment' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
