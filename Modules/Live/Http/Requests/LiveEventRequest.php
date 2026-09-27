<?php

namespace Modules\Live\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Live\Http\Entities\LiveStreamEvent;

class LiveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    LiveStreamEvent::TYPE_PRODUCT_CLICK,
                    LiveStreamEvent::TYPE_ADD_TO_CART,
                ]),
            ],
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ];
    }
}
