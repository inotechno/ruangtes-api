<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseRequest;

class UploadPaymentProofRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['sometimes', 'integer', 'exists:payments,id'],
            'transaction_id' => ['sometimes', 'integer', 'exists:public_transactions,id'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB max
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
