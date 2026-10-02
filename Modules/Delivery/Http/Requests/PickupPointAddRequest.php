<?php

namespace Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PickupPointAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:pickup_points,name'],
            'address' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_time.az' => ['required', 'string', 'max:255'],
            'delivery_time.en' => ['nullable', 'string', 'max:255'],
            'delivery_time.ru' => ['nullable', 'string', 'max:255'],
            'delivery_time.tr' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Məntəqə adı mütləq qeyd olunmalıdır.',
            'name.string' => 'Məntəqə adı düzgün formatda deyil.',
            'name.max' => 'Məntəqə adı 255 simvoldan çox ola bilməz.',
            'name.unique' => 'Bu adda məntəqə artıq mövcuddur.',

            'address.required' => 'Ünvan sahəsi boş qala bilməz.',
            'address.string' => 'Ünvan mətni düzgün deyil.',

            'price.required' => 'Çatdırılma qiyməti qeyd edilməlidir.',
            'price.numeric' => 'Qiymət yalnız rəqəmlərdən ibarət olmalıdır.',
            'price.min' => 'Qiymət mənfi ola bilməz.',

            'delivery_time.az.required' => 'Çatdırılma müddəti adı mütləq qeyd olunmalıdır.',
        ];
    }
}
