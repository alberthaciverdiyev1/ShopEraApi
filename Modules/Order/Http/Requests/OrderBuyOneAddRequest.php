<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderBuyOneAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => auth()->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
            'pay_with_balance' => ['nullable', 'boolean'],
            'promo_code' => ['nullable', 'string'],
            'size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'color_id' => ['nullable', 'integer', 'exists:colors,id'],
        ];
    }
}
