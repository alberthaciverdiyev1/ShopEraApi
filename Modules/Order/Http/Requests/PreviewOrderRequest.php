<?php

namespace Modules\Order\Http\Requests;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewOrderRequest extends FormRequest
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
            'note' => ['nullable', 'string'],
            'pay_with_balance' => ['nullable', 'boolean'],
            'promo_code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.string' => 'Qeyd mütləq mətn formatında olmalıdır.',
            'pay_with_balance.boolean' => 'Balansla ödəniş sahəsi düzgün formatda deyil (true/false).',
            'promo_code.string' => 'Promo kod mütləq mətn formatında olmalıdır.',
            'address_type.in' => 'Seçilmiş ünvan növü yanlışdır. Secilmesi m wmkwn olan wnvan novleri : STANDARD, STANDARD_FAST, PICKUP_POINT, TAKE_FROM_STORE',
            'address_type.required' => 'Ünvan növü mütləq seçilməlidir.',
            'address_type.string' => 'Ünvan növü düzgün formatda deyil.',
        ];
    }
}
