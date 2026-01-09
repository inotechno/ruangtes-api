<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;

class UpdateSubscriptionPlanRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'duration_months' => ['sometimes', 'required', 'integer', 'in:3,6,12'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
