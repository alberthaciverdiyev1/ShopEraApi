<?php

namespace Modules\Setting\Http;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instagram_url' => ['sometimes','nullable', 'string'],
            'tiktok_url' => ['sometimes','nullable', 'string'],
            'whatsapp_number' => ['sometimes','nullable', 'string'],
            'phone_number_1' => ['sometimes','nullable', 'string'],
            'phone_number_2' => ['sometimes','nullable', 'string'],
            'phone_number_3' => ['sometimes','nullable', 'string'],
            'phone_number_4' => ['sometimes','nullable', 'string'],
            'google_map_url' => ['sometimes','nullable', 'string'],
            'app_version' => ['sometimes', 'required', 'string', 'regex:/^\d+(?:\.\d+){1,2}$/'],
            'app_version_ios' => ['sometimes', 'required', 'string', 'regex:/^\d+(?:\.\d+){1,2}$/'],
            'minimal_purchase_price' => ['sometimes','required', 'numeric'],
            'wholesale_minimal_purchase_price' => ['sometimes','required', 'numeric', 'min:0'],
            'address' => ['sometimes','nullable', 'string'],
            'store_commission_percent' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'store_negative_balance_limit' => ['sometimes', 'required', 'numeric', 'min:0'],
            'store_handover_hours' => ['sometimes', 'required', 'integer', 'min:1', 'max:168'],
            'store_late_penalty_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'public_low_stock_threshold' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],
            'seller_instructions' => ['sometimes', 'nullable', 'array'],
            'seller_instructions.az' => ['nullable', 'string'],
            'seller_instructions.en' => ['nullable', 'string'],
            'seller_instructions.ru' => ['nullable', 'string'],
            'seller_instructions.tr' => ['nullable', 'string'],

        ];
    }
}
