<?php

namespace Modules\Live\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiveStreamUpdateRequest extends FormRequest
{
    use ParsesYouTubeLink;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'max:8192'],
            // Leaving `cover` out means "keep what is there", so taking a
            // cover off again needs to be said explicitly.
            'remove_cover' => ['sometimes', 'boolean'],
            'orientation' => ['sometimes', 'in:landscape,portrait'],
            'youtube_video_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
