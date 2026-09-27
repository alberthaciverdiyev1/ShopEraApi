<?php

namespace Modules\Filter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterCategoryAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
