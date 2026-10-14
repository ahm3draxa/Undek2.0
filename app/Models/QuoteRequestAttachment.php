<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequestAttachment extends Model
{
    protected $fillable = [
        'quote_request_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    /**
     * image | video | file — decides how the attachment is previewed.
     */
    public function kind(): string
    {
        return match (true) {
            str_starts_with((string) $this->mime_type, 'image/') => 'image',
            str_starts_with((string) $this->mime_type, 'video/') => 'video',
            default => 'file',
        };
    }

    public function url(): string
    {
        return route('quote-requests.attachments.show', [$this->quote_request_id, $this]);
    }
}
