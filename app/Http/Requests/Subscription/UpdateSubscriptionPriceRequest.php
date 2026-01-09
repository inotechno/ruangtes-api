<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\BaseRequest;

class UpdateSubscriptionPriceRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $priceId = $this->route('price');
        $price = \App\Models\SubscriptionPrice::find($priceId);
        $planId = $this->input('subscription_plan_id') ?? ($price ? $price->subscription_plan_id : null);

        return [
            'subscription_plan_id' => ['sometimes', 'required', 'exists:subscription_plans,id'],
            'user_quota' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) use ($planId, $priceId) {
                    if ($planId) {
                        $exists = \App\Models\SubscriptionPrice::where('subscription_plan_id', $planId)
                            ->where('user_quota', $value)
                            ->where('id', '!=', $priceId)
                            ->exists();
                        if ($exists) {
                            $fail('The user quota already exists for this subscription plan.');
                        }
                    }
                },
            ],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_per_additional_user' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
