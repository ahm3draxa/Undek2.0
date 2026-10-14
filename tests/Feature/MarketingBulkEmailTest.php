<?php

namespace Tests\Feature;

use App\Mail\MarketingCampaignMail;
use App\Models\MarketingCampaign;
use App\Models\MarketingUnsubscribe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingBulkEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $ceo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->ceo = User::where('email', 'ceo@undek.com')->first();
        Mail::fake();
    }

    private function sendManual(string $emails)
    {
        return $this->actingAs($this->ceo)->post(route('marketing.store'), [
            'subject' => 'October offer',
            'body' => '20% off websites this month.',
            'audience' => 'manual',
            'emails' => $emails,
        ]);
    }

    public function test_pasted_list_is_deduplicated_and_queued_one_email_each(): void
    {
        $this->sendManual("ali@company.com\nSara@Example.pk, ali@company.com;\n\n  bilal@shop.pk  ")
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('marketing.index'));

        Mail::assertQueued(MarketingCampaignMail::class, 3);
        Mail::assertQueued(MarketingCampaignMail::class, fn ($mail) => $mail->hasTo('sara@example.pk'));

        $campaign = MarketingCampaign::first();
        $this->assertSame('manual', $campaign->audience);
        $this->assertSame(3, $campaign->recipients()->count());
    }

    public function test_any_invalid_address_stops_the_whole_send(): void
    {
        $this->sendManual("good@company.com\nnot-an-email\nalso@bad")
            ->assertSessionHasErrors('emails');

        Mail::assertNothingQueued();
        $this->assertSame(0, MarketingCampaign::count());
    }

    public function test_unsubscribed_addresses_are_skipped(): void
    {
        MarketingUnsubscribe::create(['email' => 'gone@company.com']);

        $this->sendManual("gone@company.com\nstays@company.com")->assertSessionHasNoErrors();

        Mail::assertQueued(MarketingCampaignMail::class, 1);
        Mail::assertNotQueued(MarketingCampaignMail::class, fn ($mail) => $mail->hasTo('gone@company.com'));
    }

    public function test_campaign_is_capped_per_send(): void
    {
        config(['mail.marketing.max_per_send' => 2]);

        $this->sendManual("a@x.com\nb@x.com\nc@x.com")->assertSessionHasErrors('emails');
        Mail::assertNothingQueued();
    }

    public function test_email_is_from_undek_with_replies_to_the_specialist_and_has_unsubscribe_headers(): void
    {
        config(['mail.marketing.address' => 'marketing@undek.com']);
        $this->sendManual('client@company.com');

        Mail::assertQueued(MarketingCampaignMail::class, function (MarketingCampaignMail $mail) {
            return $mail->hasFrom('marketing@undek.com', 'Undek CEO at Undek')
                && $mail->hasReplyTo('ceo@undek.com')
                && str_contains($mail->headers()->text['List-Unsubscribe'], '/unsubscribe/client@company.com');
        });
    }

    public function test_people_without_marketing_access_cannot_send(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->post(route('marketing.store'), [
            'subject' => 'x', 'body' => 'y', 'audience' => 'manual', 'emails' => 'a@b.com',
        ])->assertForbidden();
    }

    public function test_signed_unsubscribe_link_opts_the_address_out(): void
    {
        $url = MarketingUnsubscribe::urlFor('Someone@Company.com');

        // Opening the link only shows a confirmation — mail scanners pre-fetch links.
        $this->get($url)->assertOk()->assertSee('Unsubscribe');
        $this->assertFalse(MarketingUnsubscribe::isUnsubscribed('someone@company.com'));

        // Confirming (or a mail client's one-click POST) records it, without a CSRF token.
        $this->post($url)->assertOk();
        $this->assertTrue(MarketingUnsubscribe::isUnsubscribed('someone@company.com'));
    }

    public function test_unsubscribe_link_cannot_be_forged(): void
    {
        $this->post('/unsubscribe/victim@company.com')->assertForbidden();
        $this->assertFalse(MarketingUnsubscribe::isUnsubscribed('victim@company.com'));
    }
}
