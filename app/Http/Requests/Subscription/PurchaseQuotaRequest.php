<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;

class PurchaseQuotaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'additional_users' => ['required', 'integer', 'min:1'],
        ];
    }
}
