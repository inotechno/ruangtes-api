<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CompanyResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => $this->logo,
            'is_active' => $this->is_active,
            'subscription' => $this->whenLoaded('activeSubscription', function () {
                return new CompanySubscriptionResource($this->activeSubscription);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

