<?php

namespace App\Http\Resources\Participant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantFlowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'assignment' => [
                'id' => $this->id,
                'token' => $this->unique_token,
                'start_date' => $this->start_date->toIso8601String(),
                'end_date' => $this->end_date->toIso8601String(),
                'is_completed' => $this->is_completed,
                'completed_at' => $this->completed_at?->toIso8601String(),
                'time_remaining' => now()->diffInSeconds($this->end_date, false),
            ],
            'test' => [
                'id' => $this->test->id,
                'name' => $this->test->name,
                'code' => $this->test->code,
                'description' => $this->test->description,
                'duration_minutes' => $this->test->duration_minutes,
                'question_count' => $this->test->question_count,
                'type' => $this->test->type->value,
                'category' => [
                    'id' => $this->test->category->id ?? null,
                    'name' => $this->test->category->name ?? null,
                ],
            ],
            'participant' => [
                'id' => $this->participant->id,
                'name' => $this->participant->name,
                'email' => $this->participant->email,
                'biodata' => $this->participant->biodata ?? [],
            ],
        ];
    }
}
