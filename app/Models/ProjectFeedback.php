<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeedback extends Model
{
    protected $table = 'project_feedbacks';

    public const CLIENT_TO_UNDEK = 'client_to_undek';

    public const UNDEK_TO_CLIENT = 'undek_to_client';

    protected $fillable = [
        'project_id',
        'author_id',
        'audience',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
