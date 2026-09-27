<?php

namespace Modules\PromoCode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoCodeAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:promo_codes,code|max:255',
            'discount_percent' => 'required|numeric:min:1|max:100',
            'user_count' => 'required|numeric:min:1|max:100',
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
