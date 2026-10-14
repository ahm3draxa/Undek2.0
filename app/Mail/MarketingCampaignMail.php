<?php

namespace App\Mail;

use App\Models\MarketingCampaign;
use App\Models\MarketingUnsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class MarketingCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MarketingCampaign $campaign, public string $recipientEmail)
    {
        //
    }

    public function envelope(): Envelope
    {
        $sender = $this->campaign->sender;

        return new Envelope(
            // Sent from Undek's own mailbox (so SPF/DKIM pass), shown as coming
            // from the specialist, with replies going straight to them.
            from: new Address(config('mail.marketing.address'), $sender ? "{$sender->name} at Undek" : 'Undek'),
            replyTo: $sender ? [new Address($sender->email, $sender->name)] : [],
            subject: $this->campaign->subject,
        );
    }

    /**
     * One-click unsubscribe headers — Gmail and Yahoo require these for bulk
     * senders, and without them campaigns are far more likely to land in spam.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.MarketingUnsubscribe::urlFor($this->recipientEmail).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing-campaign',
            with: [
                'body' => $this->campaign->body,
                'senderName' => $this->campaign->sender->name ?? 'Undek',
                'unsubscribeUrl' => MarketingUnsubscribe::urlFor($this->recipientEmail),
            ],
        );
    }
}
