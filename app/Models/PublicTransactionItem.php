<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_transaction_id',
        'test_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PublicTransaction::class, 'public_transaction_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
