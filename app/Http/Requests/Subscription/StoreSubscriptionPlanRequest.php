<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;

class StoreSubscriptionPlanRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'duration_months' => ['required', 'integer', 'in:3,6,12'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
