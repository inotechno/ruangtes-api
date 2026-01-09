<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SubscriptionTransactionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_subscription_id' => $this->company_subscription_id,
            'transaction_number' => $this->transaction_number,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'metadata' => $this->metadata,
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
