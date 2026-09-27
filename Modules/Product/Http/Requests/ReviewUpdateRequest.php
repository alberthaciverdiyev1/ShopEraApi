<?php

namespace Modules\Product\Http\Requests;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewUpdateRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_id' => 'required|exists:product_reviews,id',
            'status' => [
                'required',
                'string',
                Rule::in(array_column(ReviewStatus::cases(), 'name'))
            ]
        ];
    }


    public function messages(): array
    {
        return [
            'review_id.required' => 'Rəy ID-si mütləq qeyd edilməlidir.',
            'review_id.exists'   => 'Belə bir rəy tapılmadı.',
            'status.required'    => 'Status sahəsi boş qoyulmamalıdır.',
            'status.in'          => 'Yanlış status daxil edilib. Keçərli statuslar: PENDING, APPROVED, REJECTED.',
        ];
    }

}
