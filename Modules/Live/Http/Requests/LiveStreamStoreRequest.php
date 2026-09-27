<?php

namespace Modules\Live\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiveStreamStoreRequest extends FormRequest
{
    use ParsesYouTubeLink;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'orientation' => ['nullable', 'in:landscape,portrait'],
            'youtube_video_id' => ['nullable', 'string', 'max:20'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
