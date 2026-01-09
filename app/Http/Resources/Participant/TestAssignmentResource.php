<?php

namespace App\Http\Resources\Participant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestAssignmentResource extends JsonResource
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
            'unique_token' => $this->unique_token,
            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'name' => $this->test->name,
                    'code' => $this->test->code,
                    'duration_minutes' => $this->test->duration_minutes,
                ];
            }),
            'start_date' => $this->start_date->toIso8601String(),
            'end_date' => $this->end_date->toIso8601String(),
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_not_started' => $this->isNotStarted(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
