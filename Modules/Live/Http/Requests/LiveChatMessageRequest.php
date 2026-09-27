<?php

namespace Modules\Live\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiveChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:' . config('live.chat.max_length', 300)],
        ];
    }
}
