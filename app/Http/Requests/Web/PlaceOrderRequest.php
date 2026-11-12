<?php

namespace App\Http\Requests\Web;

use App\Core\Enums\AddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'address_type' => strtoupper((string) $this->input('address_type')),
            'payment_type' => strtoupper((string) $this->input('payment_type')),
        ]);
    }

    public function rules(): array
    {
        return [
            'address_type' => ['required', 'string', Rule::in(array_column(AddressType::cases(), 'name'))],
            'address_type_id' => ['nullable', 'integer'],
            'payment_type' => ['required', Rule::in(['CARD', 'CASH'])],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
