<?php

namespace App\Models;

use App\Enums\CheatDetectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheatDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'detection_type',
        'detection_data',
        'severity',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'detection_type' => CheatDetectionType::class,
            'detection_data' => 'array',
            'severity' => 'integer',
            'is_resolved' => 'boolean',
        ];
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
