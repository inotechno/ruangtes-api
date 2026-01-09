<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSessionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_session_id',
        'photo_path',
        'captured_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function testSession(): BelongsTo
    {
        return $this->belongsTo(TestSession::class);
    }
}
