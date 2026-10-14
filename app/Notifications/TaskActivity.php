<?php

namespace App\Notifications;

use App\Models\Milestone;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Milestone assigned / work submitted. Uses the same data shape as
 * NewChatMessage so the notification bell renders both the same way.
 */
class TaskActivity extends Notification
{
    public function __construct(
        public Milestone $milestone,
        public User $actor,
        public string $summary,
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
            'milestone_id' => $this->milestone->id,
            'sender_name' => $this->actor->name,
            'body' => Str::limit($this->summary, 80),
            'thread_label' => $this->milestone->project->title ?? 'Task',
            'url' => route('projects.show', $this->milestone->project_id).'#milestone-'.$this->milestone->id,
        ];
    }
}
