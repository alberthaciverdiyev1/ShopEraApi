<?php

namespace Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutoReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'question' => [$isUpdate ? 'nullable' : 'required', 'array'],
            'question.az' => [$isUpdate ? 'nullable' : 'required', 'string', 'max:1000'],
            'question.en' => ['nullable', 'string', 'max:1000'],
            'question.ru' => ['nullable', 'string', 'max:1000'],
            'question.tr' => ['nullable', 'string', 'max:1000'],

            'answer' => [$isUpdate ? 'nullable' : 'required', 'array'],
            'answer.az' => [$isUpdate ? 'nullable' : 'required', 'string'],
            'answer.en' => ['nullable', 'string'],
            'answer.ru' => ['nullable', 'string'],
            'answer.tr' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Sual sahəsi (array) göndərilməlidir.',
            'question.az.required' => 'Azərbaycan dilində sual mütləqdir.',
            'answer.required' => 'Cavab sahəsi (array) göndərilməlidir.',
            'answer.az.required' => 'Azərbaycan dilində cavab mütləqdir.',
        ];
    }
}
