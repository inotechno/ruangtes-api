<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSessionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'question_id',
        'answer',
        'is_correct',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'points' => 'integer',
        ];
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
