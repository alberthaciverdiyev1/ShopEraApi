<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Marketplace\Support\ListingFields;

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

        // Which fields this category shows, and whether they are required.
        $schema = ListingFields::forCategory((int) $this->input('category_id') ?: null);

        $text = function (string $field, array $extra = []) use ($schema): array {
            if (! $schema[$field]['visible']) {
                return ['nullable'];
            }

            return $schema[$field]['required']
                ? array_merge(['required'], $extra)
                : array_merge(['nullable'], $extra);
        };

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => $text('title', ['string', 'max:255']),
            'description' => $text('description', ['string', 'max:5000']),
            'price' => $text('price', ['numeric', 'min:0']),
            'city_key' => $text('city', ['string', Rule::exists('cities', 'key')]),

            'brand_id' => ['nullable', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:255'],

            'condition' => $schema['condition']['visible']
                ? ($schema['condition']['required'] ? ['required', Rule::in(['new', 'used'])] : ['nullable', Rule::in(['new', 'used'])])
                : ['nullable', Rule::in(['new', 'used'])],

            'has_delivery' => ['nullable', 'boolean'],
            'stock_count' => ['nullable', 'integer', 'min:1'],

            'contact_name' => [Rule::requiredIf($guest), 'nullable', 'string', 'max:255'],
            'contact_phone' => [Rule::requiredIf($guest), 'nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:190'],

            'images' => $schema['photos']['visible']
                ? ($schema['photos']['required'] ? ['required', 'array', 'min:1', 'max:10'] : ['nullable', 'array', 'max:10'])
                : ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],

            'filter_values' => ['nullable', 'array'],
            'filter_values.*' => ['integer', Rule::exists('filter_values', 'id')],
        ];
    }
}
