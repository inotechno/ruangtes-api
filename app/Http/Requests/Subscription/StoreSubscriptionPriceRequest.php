<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPriceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->input('subscription_plan_id');

        return [
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'user_quota' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('subscription_prices', 'user_quota')
                    ->where('subscription_plan_id', $planId),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'price_per_additional_user' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
