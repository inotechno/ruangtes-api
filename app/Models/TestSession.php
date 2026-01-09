<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'testable_type',
        'testable_id',
        'test_id',
        'test_assignment_id',
        'session_token',
        'started_at',
        'completed_at',
        'time_spent_seconds',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function testable(): MorphTo
    {
        return $this->morphTo();
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function testAssignment(): BelongsTo
    {
        return $this->belongsTo(TestAssignment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestSessionAnswer::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TestSessionPhoto::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TestSessionEvent::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(TestResult::class);
    }

    public function cheatDetections(): HasMany
    {
        return $this->hasMany(CheatDetection::class);
    }
}
