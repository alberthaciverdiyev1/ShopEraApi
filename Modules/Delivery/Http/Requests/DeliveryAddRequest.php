<?php

namespace Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name'     => ['required', Rule::exists('cities', 'key')->where('is_active', true)->whereNull('deleted_at')],
            'starex_region_id' => ['nullable', 'integer', 'min:1'],
            'price'         => ['required', 'numeric', 'min:0'],
            'fast_price'    => ['required', 'numeric', 'min:0'],
            'free_from'     => ['nullable', 'numeric', 'min:0'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'fast_delivery_time' => ['nullable', 'string', 'max:255'],
            'is_active'     => ['boolean'],
        ];
    }
}
