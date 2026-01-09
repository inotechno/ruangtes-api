<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'user_quota',
        'price',
        'price_per_additional_user',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_per_additional_user' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
