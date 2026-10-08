<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'city_key' => ['sometimes', 'required', 'string', Rule::exists('cities', 'key')],
            'condition' => ['sometimes', 'required', Rule::in(['new', 'used'])],
            'stock_count' => ['sometimes', 'integer', 'min:1'],

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:190'],

            'filter_values' => ['nullable', 'array'],
            'filter_values.*' => ['integer', Rule::exists('filter_values', 'id')],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
