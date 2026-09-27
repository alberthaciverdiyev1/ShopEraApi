<?php

namespace Modules\HelpAndPolicy\Http\Requests\LegalTerm;

use Illuminate\Foundation\Http\FormRequest;

class LegalTermUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return  [
            'html.az' => ['sometimes','required', 'string'],
            'html.en' => ['sometimes','nullable', 'string'],
            'html.ru' => ['sometimes','nullable', 'string'],
            'html.tr' => ['sometimes','nullable', 'string'],
            'type' => 'sometimes|nullable|string'
        ];
    }
}
