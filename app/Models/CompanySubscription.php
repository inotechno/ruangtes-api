<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanySubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'subscription_price_id',
        'user_quota',
        'used_quota',
        'status',
        'billing_type',
        'started_at',
        'expires_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscriptionPrice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPrice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public function hasAvailableQuota(): bool
    {
        return $this->used_quota < $this->user_quota;
    }

    public function getRemainingQuota(): int
    {
        return max(0, $this->user_quota - $this->used_quota);
    }
}
