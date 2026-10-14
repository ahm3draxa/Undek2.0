<?php

namespace Tests\Feature\Auth;

use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function register(string $email = 'test@gmail.com')
    {
        return $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_are_sent_a_verification_email(): void
    {
        Notification::fake();

        $this->register();

        $this->assertAuthenticated();
        $user = User::where('email', 'test@gmail.com')->first();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_users_are_held_at_the_verification_screen(): void
    {
        Notification::fake();
        $this->register();

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('test@gmail.com');
    }

    public function test_email_domain_must_accept_mail(): void
    {
        $this->register('someone@this-domain-does-not-exist-undek-test.com')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_already_registered_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@gmail.com']);

        $this->register('taken@gmail.com')->assertSessionHasErrors('email');
    }

    public function test_guest_quotes_are_only_claimed_once_the_email_is_verified(): void
    {
        Notification::fake();

        $service = Service::create(['name' => 'SEO', 'slug' => 'seo']);
        $quote = QuoteRequest::create([
            'service_id' => $service->id,
            'guest_name' => 'Real Owner',
            'guest_email' => 'owner@gmail.com',
            'title' => 'Private brief',
            'description' => 'Confidential details',
        ]);

        // Registering with someone's address is not enough to see their requests…
        $this->register('owner@gmail.com');
        $this->assertNull($quote->fresh()->client_id);

        // …proving ownership through the emailed link is.
        $user = User::where('email', 'owner@gmail.com')->first();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame($user->id, $quote->fresh()->client_id);
    }
}
