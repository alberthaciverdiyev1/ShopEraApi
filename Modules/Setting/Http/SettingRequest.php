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
            'facebook_url' => ['sometimes','nullable', 'string'],
            'twitter_url' => ['sometimes','nullable', 'string'],
            'youtube_url' => ['sometimes','nullable', 'string'],
            'telegram_url' => ['sometimes','nullable', 'string'],
            'linkedin_url' => ['sometimes','nullable', 'string'],
            'tiktok_url' => ['sometimes','nullable', 'string'],
            'whatsapp_number' => ['sometimes','nullable', 'string'],
            'phone_number_1' => ['sometimes','nullable', 'string'],
            'phone_number_2' => ['sometimes','nullable', 'string'],
            'phone_number_3' => ['sometimes','nullable', 'string'],
            'phone_number_4' => ['sometimes','nullable', 'string'],
            'google_map_url' => ['sometimes','nullable', 'string'],
            'minimal_purchase_price' => ['sometimes','required', 'numeric'],
            'wholesale_minimal_purchase_price' => ['sometimes','required', 'numeric', 'min:0'],
            'address' => ['sometimes','nullable', 'string'],
            'public_low_stock_threshold' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],

        ];
    }
}
