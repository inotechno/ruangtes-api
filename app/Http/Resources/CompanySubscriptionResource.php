<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CompanySubscriptionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'user_quota' => $this->user_quota,
            'used_quota' => $this->used_quota,
            'billing_type' => $this->billing_type,
            'started_at' => $this->started_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'plan' => $this->whenLoaded('subscriptionPlan', function () {
                return new SubscriptionPlanResource($this->subscriptionPlan);
            }),
        ];
    }
}

