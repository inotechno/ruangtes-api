<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'status' => $this->status->value,
            'proof_file' => $this->proof_file ? asset('storage/'.$this->proof_file) : null,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'payable_type' => $this->payable_type,
            'payable_id' => $this->payable_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
