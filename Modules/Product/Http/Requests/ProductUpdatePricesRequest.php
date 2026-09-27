<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdatePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => auth()->id(),
            'is_percentage' => filter_var($this->is_percentage, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['exists:products,id'],
            'confirm_all_products' => ['nullable', 'boolean'],
            'type' => ['required', 'in:increment,decrement'],
            'is_percentage' => ['boolean'],

            'price' => [
                'required_unless:is_percentage,true',
                'nullable', 'numeric', 'min:0'
            ],
            'percentage' => [
                'required_if:is_percentage,true',
                'nullable', 'numeric', 'min:0', 'max:1000'
            ],

            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100']
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->input('product_ids')) && !$this->boolean('confirm_all_products')) {
                $validator->errors()->add(
                    'confirm_all_products',
                    'Updating all products requires explicit confirmation.'
                );
            }
        });
    }

}
