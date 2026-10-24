<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Position;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shared hosting allows 500 MySQL connections an hour for the whole site, and
 * Laravel only opens one when a query runs. These tests pin down that public
 * pages run no queries at all once warm, so visitors can never exhaust the
 * allowance and take the site down.
 */
class ConnectionBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Service::create(['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Sites and apps', 'is_active' => true]);
    }

    private function queriesDuring(callable $request): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return array_column($queries, 'query');
    }

    public function test_public_pages_run_no_database_queries_once_warm(): void
    {
        // (sitemap.xml and robots.txt are static files the web server serves without PHP.)
        foreach (['/', '/quote', '/privacy-policy', '/login', '/register'] as $path) {
            $this->get($path); // warm the cache

            $queries = $this->queriesDuring(fn () => $this->get($path)->assertSuccessful());

            $this->assertSame([], $queries, "$path ran queries: ".implode(' | ', $queries));
        }
    }

    public function test_the_public_chatbot_runs_no_database_queries_once_warm(): void
    {
        $this->postJson('/chatbot', ['message' => 'tell me about web development']);

        $queries = $this->queriesDuring(fn () => $this->postJson('/chatbot', ['message' => 'web development pricing?'])->assertOk());

        $this->assertSame([], $queries);
    }

    public function test_editing_a_service_refreshes_the_cached_public_pages(): void
    {
        $this->get('/')->assertSee('Sites and apps');

        Service::first()->update(['description' => 'Fast, accessible websites']);

        $this->get('/')->assertSee('Fast, accessible websites');
        $this->get('/quote')->assertOk();
    }

    public function test_signing_in_elsewhere_signs_out_the_older_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $sessionId = session()->getId();
        $this->assertSame($sessionId, $user->fresh()->current_session_id);

        // Like a browser, keep sending this session's cookie.
        $this->withCookie(config('session.cookie'), $sessionId)->get('/dashboard')->assertOk();

        // Another device signs in: the account's current session is now that one.
        $user->forceFill(['current_session_id' => 'another-device-session'])->save();

        // Each real request loads the user afresh; the test app would otherwise
        // reuse the copy it already has in memory.
        auth()->forgetGuards();

        $this->withCookie(config('session.cookie'), $sessionId)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_admin_password_reset_signs_the_person_out(): void
    {
        $owner = User::where('email', 'ceo@undek.com')->first();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->actingAs($owner)->post(route('users.reset-password', $staff));

        $this->assertStringStartsWith('revoked:', $staff->fresh()->current_session_id);
        $this->actingAs($staff->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_with_stats_for_every_kind_of_person_in_one_request(): void
    {
        $owner = User::where('email', 'ceo@undek.com')->first();

        $sales = User::factory()->create(['name' => 'Sara Sales']);
        $sales->assignRole('staff');
        $salesPosition = Position::create(['name' => 'Account Manager', 'slug' => 'account-manager', 'department_id' => Department::where('slug', 'sales')->value('id'), 'rank' => 1]);
        EmployeeProfile::create(['user_id' => $sales->id, 'position_id' => $salesPosition->id]);

        $client = User::factory()->create(['name' => 'Kamran Client']);
        $client->assignRole('client');

        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('Needs attention')->assertSee('Payroll this month');
        $this->actingAs($sales->fresh())->get('/dashboard')->assertOk()->assertSee('Sara')->assertSee('My tasks')->assertDontSee('Payroll this month');
        $this->actingAs($client)->get('/dashboard')->assertOk()->assertSee('Your active projects')->assertDontSee('Needs attention');

        // Stats are embedded in the page, so there's no second request on load.
        $this->actingAs($owner)->get('/dashboard')->assertSee('pending_quote_requests');
        $this->actingAs($sales->fresh())->getJson('/dashboard/stats')->assertOk()->assertJsonMissingPath('payroll_this_month');
    }
}
