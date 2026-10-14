<?php

namespace Tests\Feature;

use App\Models\InboxMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    private User $ceo;

    private InboxMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->ceo = User::where('email', 'ceo@undek.com')->first();

        $this->message = InboxMessage::create([
            'mailbox' => 'support',
            'uid' => 7,
            'from_name' => 'Ayesha',
            'from_email' => 'ayesha@client.pk',
            'subject' => 'Website is down',
            'body_html' => '<p>Help!</p><script>alert(document.cookie)</script><img src="https://tracker.example/pixel.gif">',
            'received_at' => now(),
        ]);
    }

    public function test_inbox_requires_the_view_inbox_permission(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('inbox.index'))->assertForbidden();
        $this->actingAs($client)->get(route('inbox.show', $this->message))->assertForbidden();
        $this->actingAs($client)->get(route('inbox.body', $this->message))->assertForbidden();
    }

    public function test_list_shows_messages_and_unread_counts(): void
    {
        $this->actingAs($this->ceo)->get(route('inbox.index'))
            ->assertOk()
            ->assertSee('Website is down')
            ->assertSee('Ayesha');

        $this->actingAs($this->ceo)->get(route('inbox.index', ['mailbox' => 'info']))
            ->assertOk()
            ->assertDontSee('Website is down');
    }

    public function test_opening_a_message_marks_it_read_by_that_person(): void
    {
        $this->actingAs($this->ceo)->get(route('inbox.show', $this->message))->assertOk();

        $this->message->refresh();
        $this->assertNotNull($this->message->read_at);
        $this->assertSame($this->ceo->id, $this->message->read_by);

        $this->actingAs($this->ceo)->post(route('inbox.unread', $this->message));
        $this->assertNull($this->message->fresh()->read_at);
    }

    public function test_email_html_is_served_sandboxed_with_scripts_and_remote_images_blocked(): void
    {
        $response = $this->actingAs($this->ceo)->get(route('inbox.body', $this->message))->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'none'", $csp);
        $this->assertStringContainsString('sandbox', $csp);
        $this->assertStringNotContainsString('script-src', $csp);
        $this->assertStringContainsString('img-src data:;', $csp);

        $withImages = $this->actingAs($this->ceo)->get(route('inbox.body', [$this->message, 'images' => 1]));
        $this->assertStringContainsString('img-src data: https:', $withImages->headers->get('Content-Security-Policy'));
    }

    public function test_blocked_attachments_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $blocked = $this->message->attachments()->create(['original_name' => 'invoice.exe', 'size' => 1000, 'mime_type' => 'blocked']);

        $this->actingAs($this->ceo)->get(route('inbox.attachments.show', [$this->message, $blocked]))->assertNotFound();
    }

    public function test_check_now_without_configured_mailboxes_explains_itself(): void
    {
        config(['inbox.mailboxes.info.password' => null, 'inbox.mailboxes.support.password' => null]);

        $this->actingAs($this->ceo)->from(route('inbox.index'))->post(route('inbox.refresh'))
            ->assertRedirect(route('inbox.index'))
            ->assertSessionHas('status', 'No mailboxes are configured yet.');
    }
}
