<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'total_questions',
        'correct_answers',
        'wrong_answers',
        'total_points',
        'score_percentage',
        'detailed_results',
    ];

    protected function casts(): array
    {
        return [
            'score_percentage' => 'decimal:2',
            'detailed_results' => 'array',
        ];
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
