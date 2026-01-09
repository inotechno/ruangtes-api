<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_number' => $this->transaction_number,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'status' => $this->payment->status->value,
                    'amount' => (float) $this->payment->amount,
                    'payment_method' => $this->payment->payment_method,
                    'proof_url' => $this->payment->proof_url,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
