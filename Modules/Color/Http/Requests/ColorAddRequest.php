<?php

namespace Modules\Color\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ColorAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|unique:colors,name|max:255',
            'hex' => 'nullable|string',
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
