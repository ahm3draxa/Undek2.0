<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A portal copy of an email in one of the shared company mailboxes —
 * received over IMAP ('in') or sent as a reply from the portal ('out').
 */
class InboxMessage extends Model
{
    protected $fillable = [
        'mailbox',
        'direction',
        'uid',
        'message_id',
        'in_reply_to',
        'from_name',
        'from_email',
        'to',
        'cc',
        'subject',
        'body_text',
        'body_html',
        'received_at',
        'read_at',
        'read_by',
        'sent_by',
        'reply_to_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InboxAttachment::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id')->oldest('received_at');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'in');
    }

    public function scopeUnread($query)
    {
        return $query->where('direction', 'in')->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->direction === 'in' && $this->read_at === null;
    }

    public function mailboxAddress(): string
    {
        return config("inbox.mailboxes.{$this->mailbox}.address", $this->mailbox);
    }

    /**
     * A short plain-text preview for the message list.
     */
    public function preview(int $length = 140): string
    {
        $text = $this->body_text ?: strip_tags((string) $this->body_html);

        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode($text))), $length);
    }
}
