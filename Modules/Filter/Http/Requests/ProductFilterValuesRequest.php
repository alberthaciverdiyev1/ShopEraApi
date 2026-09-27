<?php

namespace Modules\Filter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterValuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'values' => ['nullable', 'array'],
            'values.*.filter_id' => ['required', 'integer', 'exists:filters,id'],
            'values.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
