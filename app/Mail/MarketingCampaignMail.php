<?php

namespace App\Mail;

use App\Models\MarketingCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
        return new Envelope(
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing-campaign',
            with: [
                'body' => $this->campaign->body,
                'senderName' => $this->campaign->sender->name ?? 'Undek',
            ],
        );
    }
}
