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

        // A generated title fills the field when the seller keeps automatic
        // naming on, so a missing title is only an error in manual mode.
        $titleRules = $schema['title']['visible'] && $schema['title']['required'] && ! $this->boolean('auto_title')
            ? ['required', 'string', 'max:255']
            : ['nullable', 'string', 'max:255'];

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => $titleRules,
            'auto_title' => ['nullable', 'boolean'],
            'description' => $text('description', ['string', 'max:5000']),
            'price' => $text('price', ['numeric', 'min:0']),
            'discount' => ['nullable', 'numeric', 'min:0', 'lt:price'],
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

            // Variants and the colour/size each photo shows.
            'colors' => ['nullable', 'array'],
            'colors.*' => ['integer', Rule::exists('colors', 'id')],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['integer', Rule::exists('sizes', 'id')],
            'size_prices' => ['nullable', 'array'],
            'size_prices.*' => ['nullable', 'numeric', 'min:0'],
            'size_discounts' => ['nullable', 'array'],
            'size_discounts.*' => ['nullable', 'numeric', 'min:0'],
            'image_colors' => ['nullable', 'array'],
            'image_colors.*' => ['nullable', 'integer', Rule::exists('colors', 'id')],
            'image_sizes' => ['nullable', 'array'],
            'image_sizes.*' => ['nullable', 'integer', Rule::exists('sizes', 'id')],

            'filter_values' => ['nullable', 'array'],
            'filter_values.*' => ['integer', Rule::exists('filter_values', 'id')],

            // Flat option labels from the storefront form, keyed by filter id.
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable'],
            'filters.*.*' => ['nullable', 'string', 'max:255'],

            'publish_as' => ['nullable', Rule::in(['store', 'personal'])],
        ];
    }
}
