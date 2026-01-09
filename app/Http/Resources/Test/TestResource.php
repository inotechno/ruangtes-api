<?php

namespace App\Http\Resources\Test;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => new TestCategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'code' => $this->code,
            'price' => $this->price,
            'question_count' => $this->question_count,
            'duration_minutes' => $this->duration_minutes,
            'type' => $this->type->value,
            'description' => $this->description,
            'instruction_route' => $this->instruction_route,
            'test_route' => $this->test_route,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
