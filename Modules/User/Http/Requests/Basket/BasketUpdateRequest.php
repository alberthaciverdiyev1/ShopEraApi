<?php

namespace Modules\User\Http\Requests\Basket;

use Illuminate\Foundation\Http\FormRequest;

class BasketUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => auth()->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'gender' => ['sometimes', 'nullable'],
            'size_id' => ['sometimes', 'nullable'],
            'color_id' => ['sometimes', 'nullable'],
            'user_id' => ['required', 'integer'],
            'selected' => ['sometimes', 'nullable'],
        ];
    }
}
