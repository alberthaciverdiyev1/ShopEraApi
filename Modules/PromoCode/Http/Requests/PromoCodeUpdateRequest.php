<?php

namespace Modules\PromoCode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoCodeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|required',
            'discount_percent' => 'sometimes|required|numeric:min:1|max:100',
            'user_count' => 'sometimes|required|numeric:min:1|max:100',
            'is_active' => ['sometimes','nullable', 'boolean'],
        ];
    }
}
