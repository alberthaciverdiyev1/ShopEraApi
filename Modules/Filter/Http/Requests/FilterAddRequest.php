<?php

namespace Modules\Filter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => fillFilterLanguages($this->input('title')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
        ];
    }
}
