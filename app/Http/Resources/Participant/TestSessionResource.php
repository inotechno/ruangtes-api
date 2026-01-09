<?php

namespace App\Http\Resources\Participant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestSessionResource extends JsonResource
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
            'session_token' => $this->session_token,
            'test' => $this->whenLoaded('test', function () {
                return [
                    'id' => $this->test->id,
                    'name' => $this->test->name,
                    'code' => $this->test->code,
                ];
            }),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
