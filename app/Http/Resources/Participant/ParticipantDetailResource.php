<?php

namespace App\Http\Resources\Participant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantDetailResource extends JsonResource
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
            'unique_token' => $this->unique_token,
            'biodata' => $this->biodata,
            'is_banned' => $this->isBanned(),
            'banned_at' => $this->banned_at?->toIso8601String(),
            'company' => $this->whenLoaded('company'),
            'test_assignments' => TestAssignmentResource::collection($this->whenLoaded('testAssignments')),
            'test_sessions' => TestSessionResource::collection($this->whenLoaded('testSessions')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
