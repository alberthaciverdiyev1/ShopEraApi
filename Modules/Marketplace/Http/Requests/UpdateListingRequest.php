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
            // A partial update may omit the price; only compare when it is sent.
            'discount' => $this->filled('price')
                ? ['nullable', 'numeric', 'min:0', 'lt:price']
                : ['nullable', 'numeric', 'min:0'],
            'city_key' => ['sometimes', 'required', 'string', Rule::exists('cities', 'key')],
            'condition' => ['sometimes', 'required', Rule::in(['new', 'used'])],
            'has_delivery' => ['nullable', 'boolean'],
            'stock_count' => ['sometimes', 'integer', 'min:0'],

            // Variants and the colour/size each photo shows, matching the
            // create contract so the management page can edit them too.
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

            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:190'],

            'filter_values' => ['nullable', 'array'],
            'filter_values.*' => ['integer', Rule::exists('filter_values', 'id')],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    /**
     * A size's sale price has to stay below its regular price; the field-level
     * `lt` rule cannot compare two array entries.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if (filter_var($this->input('standard_price'), FILTER_VALIDATE_BOOLEAN)) {
                    return;
                }

                $prices = (array) $this->input('size_prices', []);
                $discounts = (array) $this->input('size_discounts', []);

                foreach ($discounts as $sizeId => $discount) {
                    $price = $prices[$sizeId] ?? null;

                    if (blank($discount) || blank($price)) {
                        continue;
                    }

                    if ((float) $discount >= (float) $price) {
                        $validator->errors()->add(
                            'size_discounts.'.$sizeId,
                            'Ölçü üzrə endirimli qiymət adi qiymətdən aşağı olmalıdır.'
                        );
                    }
                }
            },
        ];
    }
}
