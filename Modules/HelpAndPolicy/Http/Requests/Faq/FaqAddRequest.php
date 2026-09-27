<?php

namespace Modules\HelpAndPolicy\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

class FaqAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return  [
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'description.az' => ['required', 'string', 'max:255'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ru' => ['nullable', 'string', 'max:255'],
            'description.tr' => ['nullable', 'string', 'max:255'],
            'type' => 'required',
        ];
    }

}
