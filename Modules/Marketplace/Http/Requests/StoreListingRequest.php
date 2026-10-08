<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Guests (no account) must leave contact details; signed-in sellers
        // fall back to their profile.
        $guest = $this->user('sanctum') === null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'city_key' => ['required', 'string', Rule::exists('cities', 'key')],
            'condition' => ['required', Rule::in(['new', 'used'])],
            'stock_count' => ['nullable', 'integer', 'min:1'],

            'contact_name' => [Rule::requiredIf($guest), 'nullable', 'string', 'max:255'],
            'contact_phone' => [Rule::requiredIf($guest), 'nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:190'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
