<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function prepareForValidation()
    {
        $purchaseLimit = $this->input('purchase_limit');

        return $this->merge([
            'user_id' => auth()->id(),
            'purchase_limit' => $purchaseLimit === '' ? null : $purchaseLimit,
        ]);

    }

    public function rules(): array
    {
        return [
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],

            'description.az' => ['required', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],


            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],

            'brand_id' => ['nullable', 'exists:brands,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'kids','unisex'])],
            'category_id' => ['nullable', 'exists:categories,id'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_expire_date' => ['nullable', 'date'],

            'stock_count' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0.001'],
            'purchase_limit'=>['nullable','integer','min:0'],

            'is_active' => ['boolean'],
            'is_pinned' => ['boolean','nullable'],
            'is_suggest' => ['boolean'],
            'views' => ['nullable', 'integer', 'min:0'],
            'sales_count' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['required', 'exists:users,id'],

            'colors' => ['nullable', 'array'],
            'colors.*' => ['exists:colors,id'],

            'sizes' => ['nullable', 'array'],
            'sizes.*.size_id' => ['exists:sizes,id'],
            // A size without its own price inherits the product's price — the
            // pricing service already falls back that way, and the update
            // request has always allowed it. Requiring it here meant a seller
            // who picked three sizes could not save without typing three
            // prices that were the same as the product's.
            'sizes.*.price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.discount' => ['nullable', 'numeric', 'min:0'],


            'images'   => ['nullable', 'array'],
//            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'],

            'images.*.file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'],
            'images.*.color_id' => ['nullable', 'exists:colors,id'],

            'videos'   => ['nullable', 'array'],
            'videos.*' => [
                'file',
                'max:512000',
               // 'mimes: mp4, mov, avi, webm, mkv, flv, wmv, mpg, mpeg, m4v, 3gp, 3g2, ogv, ts, vob'
            ],

        ];
    }
}
