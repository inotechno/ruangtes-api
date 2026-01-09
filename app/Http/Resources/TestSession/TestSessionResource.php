<?php

namespace App\Http\Resources\TestSession;

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
            'status' => $this->status,
            'started_at' => $this->started_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'time_spent_seconds' => $this->time_spent_seconds,
            'test' => [
                'id' => $this->test->id,
                'name' => $this->test->name,
                'code' => $this->test->code,
                'duration_minutes' => $this->test->duration_minutes,
                'question_count' => $this->test->question_count,
            ],
            'assignment' => $this->when($this->testAssignment, [
                'id' => $this->testAssignment->id,
                'token' => $this->testAssignment->unique_token,
                'start_date' => $this->testAssignment->start_date->toIso8601String(),
                'end_date' => $this->testAssignment->end_date->toIso8601String(),
            ]),
            'answers' => $this->whenLoaded('answers', function () {
                return $this->answers->map(function ($answer) {
                    return [
                        'question_id' => $answer->question_id,
                        'answer' => $answer->answer,
                        'is_correct' => $answer->is_correct,
                        'points' => $answer->points,
                        'updated_at' => $answer->updated_at->toIso8601String(),
                    ];
                });
            }),
            'metadata' => $this->metadata,
        ];
    }
}
