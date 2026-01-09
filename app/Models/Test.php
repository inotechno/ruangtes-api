<?php

namespace App\Models;

use App\Enums\TestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'price',
        'question_count',
        'duration_minutes',
        'type',
        'description',
        'instruction_route',
        'test_route',
        'metadata',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => TestType::class,
            'price' => 'decimal:2',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TestCategory::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    public function testAssignments(): HasMany
    {
        return $this->hasMany(TestAssignment::class);
    }
}
