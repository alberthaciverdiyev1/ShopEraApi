<?php

namespace Modules\Chat\Http;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'],
            'target_user_id' => 'nullable|integer|exists:users,id',
            'message' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Şəkil yükləmək məcburidir.',
            'image.file' => 'Yüklənən fayl düzgün deyil.',
            'image.image' => 'Yüklənən fayl şəkil formatında olmalıdır.',
            'image.mimes' => 'Şəklin formatı düzgün deyil. İcazə verilən formatlar: jpg, jpeg, png, webp, gif, svg, bmp, tiff, avif.',
        ];
    }
}
