<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Test\TestCatalogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
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
            'test' => new TestCatalogResource($this->whenLoaded('test')),
            'test_id' => $this->test_id,
            'price' => (float) $this->price,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
