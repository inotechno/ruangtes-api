<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => $this->logo,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'admins_count' => $this->whenCounted('admins'),
            'participants_count' => $this->whenCounted('participants'),
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'active_subscription' => $this->whenLoaded('activeSubscription'),
            'admins' => CompanyAdminResource::collection($this->whenLoaded('admins')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'subscriptions' => $this->whenLoaded('subscriptions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
