<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewChatMessage extends Notification
{
    public function __construct(
        public Message $message,
        public string $threadLabel,
        public string $url,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_name' => $this->message->user->name ?? 'Someone',
            'body' => Str::limit($this->message->body, 80),
            'thread_label' => $this->threadLabel,
            'url' => $this->url,
        ];
    }
}
