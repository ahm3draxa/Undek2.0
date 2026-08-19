<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $fillable = [
        'quote_request_id',
        'specialist_id',
        'proposed_by_id',
        'budget',
        'currency',
        'estimated_days',
        'delivery_date',
        'notes',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'budget' => 'decimal:2',
            'delivery_date' => 'date',
        ];
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_id');
    }

    /**
     * Whether the client (rather than the specialist/staff side) made this proposal —
     * determines who is allowed to accept/reject it and who may counter it next.
     */
    public function wasProposedByClient(): bool
    {
        return $this->proposed_by_id !== null && $this->proposed_by_id === $this->quoteRequest->client_id;
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
}
