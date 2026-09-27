<?php

namespace Modules\Balance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'amount'  => 'required|numeric|min:0.01',
            'note'    => 'nullable|string',
            'type'    => 'nullable|string',
        ];
    }

}
