<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;

class ExtendSubscriptionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
