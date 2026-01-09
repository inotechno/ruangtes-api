<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class InvoiceResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_subscription_id' => $this->company_subscription_id,
            'invoice_number' => $this->invoice_number,
            'amount' => $this->amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'due_date' => $this->due_date->toDateString(),
            'paid_date' => $this->paid_date?->toDateString(),
            'status' => $this->status,
            'items' => $this->items,
            'is_overdue' => $this->isOverdue(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
