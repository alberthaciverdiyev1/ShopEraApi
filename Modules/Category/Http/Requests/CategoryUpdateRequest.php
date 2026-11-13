<?php

namespace Modules\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name.az' => ['sometimes', 'required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.ru' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],

            'image' => ['nullable'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'size_group' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
