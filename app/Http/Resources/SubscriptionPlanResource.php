<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SubscriptionPlanResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration_months' => $this->duration_months,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'prices' => $this->whenLoaded('prices', function () {
                return SubscriptionPriceResource::collection($this->prices);
            }),
        ];
    }
}

