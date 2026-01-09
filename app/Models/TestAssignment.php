<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'test_id',
        'unique_token',
        'start_date',
        'end_date',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    public function isExpired(): bool
    {
        return now() > $this->end_date;
    }

    public function isNotStarted(): bool
    {
        return now() < $this->start_date;
    }
}
