<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SubscriptionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'subscription_price' => new SubscriptionPriceResource($this->whenLoaded('subscriptionPrice')),
            'user_quota' => $this->user_quota,
            'used_quota' => $this->used_quota,
            'remaining_quota' => $this->getRemainingQuota(),
            'status' => $this->status->value,
            'billing_type' => $this->billing_type,
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'transactions' => SubscriptionTransactionResource::collection($this->whenLoaded('transactions')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
