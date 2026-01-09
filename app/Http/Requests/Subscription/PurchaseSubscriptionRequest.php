<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PurchaseSubscriptionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'subscription_price_id' => [
                'required',
                'exists:subscription_prices,id',
                Rule::exists('subscription_prices', 'id')->where(function ($query) {
                    return $query->where('subscription_plan_id', $this->input('subscription_plan_id'))
                        ->where('is_active', true);
                }),
            ],
            'billing_type' => ['required', 'in:pre_paid,post_paid'],
            'additional_users' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
