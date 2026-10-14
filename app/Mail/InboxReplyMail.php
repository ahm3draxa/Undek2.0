<?php

namespace App\Mail;

use App\Models\InboxMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * A reply sent from the portal inbox, from the shared mailbox itself, threaded
 * onto the original so it lands in the same conversation for the recipient.
 */
class InboxReplyMail extends Mailable
{
    public function __construct(
        public InboxMessage $original,
        public string $replySubject,
        public string $replyBody,
        public string $messageId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->original->mailboxAddress(), 'Undek'),
            subject: $this->replySubject,
        );
    }

    public function headers(): Headers
    {
        $references = $this->original->message_id ? [trim($this->original->message_id, '<>')] : [];

        return new Headers(
            messageId: $this->messageId,
            references: $references,
            text: $this->original->message_id ? ['In-Reply-To' => '<'.trim($this->original->message_id, '<>').'>'] : [],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.inbox-reply');
    }
}
