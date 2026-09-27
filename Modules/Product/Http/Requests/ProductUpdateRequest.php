<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'title.az' => ['sometimes', 'required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],

            'description.az' => ['sometimes', 'required', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],

            'discount_expire_date' => ['nullable', 'date'],
            'purchase_limit' => ['nullable', 'integer', 'min:0'],
            'is_pinned' => ['boolean','nullable'],


            'sku' => ['nullable', 'string', 'max:50'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'kids', 'unisex'])],
            'category_id' => ['nullable', 'exists:categories,id'],

            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'wholesale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_count' => ['sometimes', 'required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0.001'],
            'is_active' => ['nullable', 'boolean'],
            'views' => ['nullable', 'integer', 'min:0'],
            'sales_count' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['nullable', 'exists:users,id'],
            'is_suggest' => ['nullable', 'boolean'],

            'colors' => ['nullable', 'array'],
            //  'colors.*' => ['exists:colors,id'],
            'sizes' => ['nullable', 'array'],
            'sizes.*.size_id' => ['exists:sizes,id'],
            'sizes.*.price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'sizes.*.discount' => ['nullable', 'numeric', 'min:0'],

            // 'sizes.*' => ['exists:sizes,id'],

            // Multipart cannot express an empty array, so an app that has just
            // had its last colour or its last photo removed sends no key at all
            // and the server reads that as "unchanged". These say "I sent you my
            // complete list, even if it is empty". Old clients omit them and
            // keep the previous behaviour exactly.
            'colors_synced' => ['sometimes', 'boolean'],
            'images_synced' => ['sometimes', 'boolean'],

            'existing_images' => ['nullable', 'array'],
            'existing_images.*.id' => ['required', 'integer', 'exists:product_image,id'],
            'existing_images.*.color_id' => ['nullable', 'integer', 'exists:colors,id'],

            'images'            => ['nullable', 'array'],

            'images.*.file'     => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif',
                'max:5120'
            ],

            // Nullable, matching the create request. `required` here meant a
            // seller adding one more photo to an existing product got a 422
            // unless they assigned it a colour — and the published app omits
            // the key entirely when no colour is picked, so it could not even
            // satisfy the rule.
            'images.*.color_id' => [
                'nullable',
                'integer',
                'exists:colors,id'
            ],


            'videos' => ['nullable', 'array'],
            'videos.*' => [
                'file',
                //'mimes: mp4, mov, avi, webm, mkv, flv, wmv, mpg, mpeg, m4v, 3gp, 3g2, ogv, ts, vob'
            ],
            'existing_videos' => ['nullable', 'array'],

        ];
    }

}
