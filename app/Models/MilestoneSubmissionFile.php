<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneSubmissionFile extends Model
{
    protected $fillable = [
        'milestone_submission_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MilestoneSubmission::class, 'milestone_submission_id');
    }
}
