<?php

namespace Modules\Popup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PopupAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'show_on_home_page' => $this->show_on_home_page ?? false,
        ]);
    }


    public function rules(): array
    {
        return [
            'image' => [
                'required_without:video',
                'prohibits:video',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'
            ],
            'video' => [
                'required_without:image',
                'file',
                'max:512000'
            ],
            'show_on_home_page' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Şəkil yükləmək məcburidir.',
            'image.file' => 'Yüklənən fayl düzgün deyil.',
            'image.image' => 'Yüklənən fayl şəkil formatında olmalıdır.',
            'image.mimes' => 'Şəklin formatı düzgün deyil. İcazə verilən formatlar: jpg, jpeg, png, webp, gif, svg, bmp, tiff, avif.',

            'show_on_home_page.boolean' => 'Ana səhifədə göstərmə sahəsi yalnız true və ya false ola bilər.',
        ];
    }
}
