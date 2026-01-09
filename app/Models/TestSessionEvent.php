<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSessionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'event_type',
        'event_data',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
