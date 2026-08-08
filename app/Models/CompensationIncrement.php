<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompensationIncrement extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'direction',
        'value',
        'field',
        'old_value',
        'new_value',
        'effective_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'value' => 'decimal:2',
            'old_value' => 'decimal:2',
            'new_value' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
