<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseRequest;

class VerifyPaymentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'exists:payments,id'],
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
