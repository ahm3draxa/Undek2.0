<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxAttachment extends Model
{
    protected $fillable = [
        'inbox_message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(InboxMessage::class, 'inbox_message_id');
    }

    /**
     * False when the file was blocked (dangerous type) or too large to keep.
     */
    public function isStored(): bool
    {
        return $this->disk !== null && $this->path !== null;
    }
}
