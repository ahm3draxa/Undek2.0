<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Message;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\NewChatMessage;
use Illuminate\Support\Facades\Notification;

trait NotifiesThreadParticipants
{
    /**
     * Creates a message (a typed chat message, or an automatic "X happened" event
     * log entry) on the given thread and notifies everyone with access to it,
     * except whoever triggered it.
     */
    protected function postToThread(QuoteRequest|Project $parent, User $actor, string $body, string $type = 'message', string $channel = 'client'): Message
    {
        $message = $parent->messages()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'type' => $type,
            'channel' => $channel,
        ]);

        $message->setRelation('user', $actor);

        $this->notifyParticipants($parent, $message, $channel);

        return $message;
    }

    protected function notifyParticipants(QuoteRequest|Project $parent, Message $message, string $channel): void
    {
        $recipients = match (true) {
            $parent instanceof QuoteRequest => collect([$parent->client, $parent->assignedSpecialist]),
            $channel === 'team' => collect([$parent->leadExpert])
                ->concat($parent->teamMembers()->with('user')->get()->pluck('user'))
                ->concat(User::role(['ceo', 'admin'])->get()),
            default => collect([$parent->client, $parent->leadExpert])
                ->concat($parent->teamMembers()->with('user')->get()->pluck('user')),
        };

        $recipients = $recipients->filter()->unique('id')->reject(fn (User $u) => $u->id === $message->user_id);

        if ($recipients->isEmpty()) {
            return;
        }

        $url = $parent instanceof QuoteRequest
            ? route('quote-requests.show', $parent)
            : route('projects.show', $parent);

        Notification::send($recipients, new NewChatMessage($message, $parent->title, $url));
    }
}
