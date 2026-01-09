<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SubscriptionPriceResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_quota' => $this->user_quota,
            'price' => (float) $this->price,
            'price_per_additional_user' => $this->price_per_additional_user ? (float) $this->price_per_additional_user : null,
            'is_active' => $this->is_active,
        ];
    }
}

