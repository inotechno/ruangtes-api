<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PublicUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'date_of_birth',
        'address',
        'biodata',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'biodata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'user_id', 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PublicTransaction::class, 'user_id', 'user_id');
    }

    public function testSessions(): HasMany
    {
        return $this->morphMany(TestSession::class, 'testable');
    }
}
