<?php

namespace Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name'     => ['sometimes', 'required', Rule::exists('cities', 'key')->where('is_active', true)->whereNull('deleted_at')],
            'starex_region_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'price'         => ['sometimes', 'required', 'numeric', 'min:0'],
            'fast_price'         => ['sometimes', 'required', 'numeric', 'min:0'],
            'free_from'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'delivery_time' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fast_delivery_time' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
