<?php

namespace Modules\Size\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SizeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sizeId = (int) $this->route('id');
        return [
            'name' => [
                "sometimes",
                'required',
                'string',
                'max:255',
                Rule::unique('sizes', 'name')->ignore($sizeId, 'id'),
            ],
            'image' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
