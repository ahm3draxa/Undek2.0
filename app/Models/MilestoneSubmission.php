<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Work handed in against a milestone — a note, files, or both — optionally
 * marking the milestone done in the same step.
 */
class MilestoneSubmission extends Model
{
    protected $fillable = [
        'milestone_id',
        'user_id',
        'note',
        'marked_done',
    ];

    protected function casts(): array
    {
        return [
            'marked_done' => 'boolean',
        ];
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(MilestoneSubmissionFile::class);
    }
}
