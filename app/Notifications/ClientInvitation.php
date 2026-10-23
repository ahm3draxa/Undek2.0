<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Sent when staff set up a project for a client who has no account yet: a
 * link to choose their own password and see the project in the portal.
 */
class ClientInvitation extends Notification
{
    public function __construct(public string $projectTitle) {}

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => Password::broker()->createToken($notifiable),
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Your Undek project is set up')
            ->greeting("Hi {$notifiable->name},")
            ->line("We've set up your project \"{$this->projectTitle}\" in the Undek client portal, where you can follow milestones and talk to the team.")
            ->action('Choose your password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire', 60).' minutes. After that, use "Forgot password" on the login page with this email address.');
    }
}
