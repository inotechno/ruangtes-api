<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'user_quota' => $this->user_quota,
            'user_quota_label' => $this->user_quota === 0 ? 'Custom' : $this->user_quota,
            'price' => $this->price,
            'price_per_additional_user' => $this->price_per_additional_user,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
