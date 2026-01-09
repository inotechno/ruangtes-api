<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'unique_token',
        'biodata',
        'banned_at',
    ];

    protected function casts(): array
    {
        return [
            'biodata' => 'array',
            'banned_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function testAssignments(): HasMany
    {
        return $this->hasMany(TestAssignment::class);
    }

    public function testSessions(): HasMany
    {
        return $this->morphMany(TestSession::class, 'testable');
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }
}
