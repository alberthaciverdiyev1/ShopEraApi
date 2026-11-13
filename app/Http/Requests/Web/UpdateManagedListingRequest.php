<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManagedListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mirrors the marketplace update rules; the manage page only exposes a
     * subset, but partial updates keep the same contract as the API.
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'condition' => ['sometimes', 'required', 'in:new,used'],
            'has_delivery' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_count' => ['sometimes', 'integer', 'min:0'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['integer', 'exists:colors,id'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['integer', 'exists:sizes,id'],
            'size_prices' => ['nullable', 'array'],
            'size_prices.*' => ['nullable', 'numeric', 'min:0'],
            'size_discounts' => ['nullable', 'array'],
            'size_discounts.*' => ['nullable', 'numeric', 'min:0'],
            'size_stocks' => ['nullable', 'array'],
            'size_stocks.*' => ['nullable', 'integer', 'min:0'],
            'standard_price' => ['nullable', 'boolean'],
            'image_colors' => ['nullable', 'array'],
            'image_colors.*' => ['nullable', 'integer', 'exists:colors,id'],
            'image_sizes' => ['nullable', 'array'],
            'image_sizes.*' => ['nullable', 'integer', 'exists:sizes,id'],
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
