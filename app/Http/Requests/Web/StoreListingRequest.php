<?php

namespace App\Http\Requests\Web;

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
        $guest = $this->user() === null;
        $schema = ListingFields::forCategory((int) $this->input('category_id') ?: null);

        $text = function (string $field, array $extra = []) use ($schema): array {
            if (! $schema[$field]['visible']) {
                return ['nullable'];
            }

            return $schema[$field]['required']
                ? array_merge(['required'], $extra)
                : array_merge(['nullable'], $extra);
        };

        // With automatic naming on the title is generated, so it may be empty.
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

            // Variants: the colours and sizes the listing comes in, with an
            // optional price and discount per size (e.g. bigger shoe sizes
            // cost more), and the colour/size each uploaded photo shows.
            'colors' => ['nullable', 'array'],
            'colors.*' => ['integer', Rule::exists('colors', 'id')],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['integer', Rule::exists('sizes', 'id')],
            'size_prices' => ['nullable', 'array'],
            'size_prices.*' => ['nullable', 'numeric', 'min:0'],
            'size_discounts' => ['nullable', 'array'],
            'size_discounts.*' => ['nullable', 'numeric', 'min:0'],
            'size_stocks' => ['nullable', 'array'],
            'size_stocks.*' => ['nullable', 'integer', 'min:0'],
            'standard_price' => ['nullable', 'boolean'],
            'image_colors' => ['nullable', 'array'],
            'image_colors.*' => ['nullable', 'integer', Rule::exists('colors', 'id')],
            'image_sizes' => ['nullable', 'array'],
            'image_sizes.*' => ['nullable', 'integer', Rule::exists('sizes', 'id')],

            'filter_values' => ['nullable', 'array'],
            'filter_values.*' => ['integer', Rule::exists('filter_values', 'id')],

            // The storefront form posts flat option labels: filters[id] = value
            // for single-choice filters and filters[id][] = value for checkbox
            // filters. They are stored in product_filters, the table the shop
            // sidebar reads.
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable'],
            'filters.*.*' => ['nullable', 'string', 'max:255'],

            // Store owners (and team members with the listings permission)
            // choose whether the listing belongs to the store or to them.
            'publish_as' => ['nullable', Rule::in(['store', 'personal'])],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kateqoriya seçilməlidir.',
            'category_id.exists' => 'Kateqoriya tapılmadı.',
            'city_key.exists' => 'Şəhər tapılmadı.',
            'brand_id.exists' => 'Marka tapılmadı.',
            'images.*.mimes' => 'Yalnız JPG, PNG və WEBP formatları qəbul edilir.',
            'images.*.max' => 'Hər şəkil maksimum 8MB olmalıdır.',
            'discount.lt' => 'Endirimli qiymət əsas qiymətdən kiçik olmalıdır.',
        ];
    }
}
