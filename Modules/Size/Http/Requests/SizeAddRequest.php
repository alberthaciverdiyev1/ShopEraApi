<?php

namespace Modules\Size\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SizeAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:sizes,name|max:255',
            'image' => 'nullable|string',
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
