<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class SendSupportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:2000', 'required_without:image'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'],
        ];
    }
}
