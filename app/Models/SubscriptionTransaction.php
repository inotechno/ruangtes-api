<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_subscription_id',
        'transaction_number',
        'amount',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'metadata' => 'array',
        ];
    }

    public function companySubscription(): BelongsTo
    {
        return $this->belongsTo(CompanySubscription::class);
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::Failed;
    }
}
