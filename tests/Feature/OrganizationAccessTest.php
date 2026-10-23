<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\InboxMessage;
use App\Models\Position;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Notifications\ClientInvitation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class OrganizationAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->owner = User::where('email', 'ceo@undek.com')->first();
    }

    private function person(string $role, ?string $department = null, array $profile = []): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        if ($department) {
            $position = Position::create([
                'name' => "$department role ".$user->id,
                'slug' => "$department-role-".$user->id,
                'department_id' => Department::where('slug', $department)->value('id'),
                'rank' => 1,
            ]);
            EmployeeProfile::create(['user_id' => $user->id, 'position_id' => $position->id] + $profile);
        }

        return $user->fresh();
    }

    // ── Roles & accounts ────────────────────────────────────────────────

    public function test_admin_cannot_promote_anyone_to_owner_or_touch_owner_accounts(): void
    {
        $admin = $this->person('admin');
        $staff = $this->person('staff');

        $this->actingAs($admin)->patch(route('users.role', $admin), ['role' => 'owner'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch(route('users.role', $staff), ['role' => 'owner'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch(route('users.role', $staff), ['role' => 'admin'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('users.reset-password', $this->owner))->assertForbidden();
        $this->actingAs($admin)->patch(route('users.update-email', $this->owner), ['email' => 'x@gmail.com'])->assertForbidden();
        $this->actingAs($admin)->patch(route('users.toggle-active', $this->owner))->assertForbidden();

        $this->assertTrue($this->owner->fresh()->isOwner());
        $this->assertTrue($this->owner->fresh()->is_active);
        $this->assertFalse($admin->fresh()->isOwner());
    }

    public function test_admin_can_still_manage_staff_and_clients(): void
    {
        $admin = $this->person('admin');
        $staff = $this->person('staff');

        $this->actingAs($admin)->patch(route('users.role', $staff), ['role' => 'client'])->assertRedirect();
        $this->assertTrue($staff->fresh()->hasRole('client'));
    }

    public function test_owner_can_make_a_co_founder_owner_but_the_last_owner_cannot_step_down(): void
    {
        $coFounder = $this->person('staff');

        $this->actingAs($this->owner)->patch(route('users.role', $coFounder), ['role' => 'owner'])->assertRedirect();
        $this->assertTrue($coFounder->fresh()->isOwner());

        $this->actingAs($coFounder)->patch(route('users.role', $coFounder), ['role' => 'staff'])->assertRedirect();
        $this->assertFalse($coFounder->fresh()->isOwner());

        $this->actingAs($this->owner)->patch(route('users.role', $this->owner), ['role' => 'staff'])->assertStatus(422);
        $this->assertTrue($this->owner->fresh()->isOwner());
    }

    public function test_deactivated_people_cannot_log_in_and_are_signed_out_immediately(): void
    {
        $staff = $this->person('staff');

        $this->actingAs($this->owner)->patch(route('users.toggle-active', $staff));
        $this->assertFalse($staff->fresh()->is_active);

        auth()->logout();
        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // An already-open session is ended on its next request.
        $this->actingAs($staff->fresh())->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_two_factor_codes_are_limited_to_five_tries(): void
    {
        $google2fa = new Google2FA;
        $user = $this->person('staff');
        $user->forceFill(['two_factor_secret' => $google2fa->generateSecretKey(), 'two_factor_confirmed_at' => now()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));

        foreach (range(1, 5) as $attempt) {
            $this->post(route('two-factor.login.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        }

        // Sixth try: the half-finished login is discarded, even with the right code.
        $this->post(route('two-factor.login.store'), ['code' => $google2fa->getCurrentOtp($user->two_factor_secret)])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_onboarded_employees_can_use_the_portal_straight_away_and_admins_cannot_onboard_owners(): void
    {
        $admin = $this->person('admin');
        $form = ['name' => 'New Hire', 'email' => 'newhire@gmail.com', 'employment_type' => 'full_time', 'compensation_type' => 'fixed_salary', 'base_salary' => 1000];

        $this->actingAs($admin)->post(route('employees.store'), $form + ['role' => 'owner'])->assertSessionHasErrors('role');

        $this->actingAs($admin)->post(route('employees.store'), $form + ['role' => 'staff'])->assertSessionHasNoErrors();
        $hire = User::where('email', 'newhire@gmail.com')->first();
        $this->assertTrue($hire->hasVerifiedEmail());
        $this->actingAs($hire)->get('/dashboard')->assertOk();
    }

    // ── Teams & tools ───────────────────────────────────────────────────

    public function test_team_tools_follow_the_department_not_skills(): void
    {
        $marketer = $this->person('staff', 'seo-marketing');
        $developer = $this->person('staff', 'web-development');

        // A Marketing-category skill used to unlock bulk email. It no longer does.
        $category = SkillCategory::firstOrCreate(['slug' => 'marketing'], ['name' => 'Marketing']);
        $developer->skills()->attach(Skill::create(['name' => 'SEO', 'slug' => 'seo', 'category_id' => $category->id])->id, ['level' => 'advanced']);

        $this->actingAs($marketer)->get(route('marketing.index'))->assertOk();
        $this->actingAs($developer)->get(route('marketing.index'))->assertForbidden();
        $this->assertTrue($developer->can('lead projects'));
        $this->assertFalse($developer->can('send quotation'));
    }

    public function test_moving_someone_to_another_team_swaps_their_tools(): void
    {
        $person = $this->person('staff', 'seo-marketing');
        $this->assertTrue($person->can('send marketing'));

        $sales = Position::create(['name' => 'Sales Executive', 'slug' => 'sales-executive', 'department_id' => Department::where('slug', 'sales')->value('id'), 'rank' => 1]);
        $person->employeeProfile->update(['position_id' => $sales->id]);

        $person = $person->fresh();
        $this->assertFalse($person->can('send marketing'));
        $this->assertTrue($person->can('send quotation'));
    }

    public function test_editing_a_teams_tools_updates_its_members_straight_away(): void
    {
        $designer = $this->person('staff', 'design-video');
        $this->assertFalse($designer->can('send marketing'));

        $this->actingAs($this->owner)->put(route('roles.departments.update', Department::where('slug', 'design-video')->first()), [
            'permissions' => ['lead projects', 'assign project team', 'send marketing', 'manage users'],
        ])->assertRedirect();

        $designer = $designer->fresh();
        $this->assertTrue($designer->can('send marketing'));
        // Account administration can never be handed out as a team tool.
        $this->assertFalse($designer->can('manage users'));
    }

    public function test_each_inbox_is_only_visible_to_its_team(): void
    {
        $support = InboxMessage::create(['mailbox' => 'support', 'uid' => 1, 'subject' => 'Help', 'from_email' => 'a@b.com', 'received_at' => now()]);
        $info = InboxMessage::create(['mailbox' => 'info', 'uid' => 1, 'subject' => 'Partnership', 'from_email' => 'c@d.com', 'received_at' => now()]);

        $itSupport = $this->person('staff', 'it-support');
        $management = $this->person('staff', 'management');
        $developer = $this->person('staff', 'web-development');

        $this->actingAs($itSupport)->get(route('inbox.index'))->assertOk()->assertSee('Help')->assertDontSee('Partnership');
        $this->actingAs($itSupport)->get(route('inbox.show', $info))->assertForbidden();
        $this->actingAs($management)->get(route('inbox.show', $info))->assertOk();
        $this->actingAs($management)->get(route('inbox.show', $support))->assertForbidden();
        $this->actingAs($developer)->get(route('inbox.index'))->assertForbidden();
    }

    public function test_interns_cannot_lead_projects_even_in_a_delivery_team(): void
    {
        $intern = $this->person('staff', 'web-development', ['employment_type' => 'intern']);
        $developer = $this->person('staff', 'web-development');
        $service = Service::create(['name' => 'Web Development', 'slug' => 'web-development', 'department_id' => Department::where('slug', 'web-development')->value('id')]);

        $response = $this->actingAs($this->owner)->get(route('projects.create'))->assertOk();
        $response->assertSee($developer->name)->assertDontSee($intern->name);

        $this->actingAs($this->owner)->post(route('projects.store'), [
            'title' => 'X', 'service_id' => $service->id, 'client_mode' => 'internal', 'lead_expert_id' => $intern->id,
        ])->assertStatus(422);
    }

    // ── Manual projects ─────────────────────────────────────────────────

    public function test_manual_project_for_a_new_client_creates_the_full_chain_and_invites_them(): void
    {
        Notification::fake();
        $service = Service::create(['name' => 'SEO', 'slug' => 'seo']);
        $lead = $this->person('staff', 'seo-marketing');

        $this->actingAs($this->owner)->post(route('projects.store'), [
            'title' => 'WhatsApp client SEO',
            'service_id' => $service->id,
            'client_mode' => 'new',
            'new_client_name' => 'Bilal',
            'new_client_email' => 'bilal@gmail.com',
            'budget' => 500,
            'currency' => 'PKR',
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'lead_expert_id' => $lead->id,
            'milestones' => [['title' => 'Audit', 'due_date' => ''], ['title' => '', 'due_date' => '']],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $client = User::where('email', 'bilal@gmail.com')->first();
        $this->assertTrue($client->hasRole('client'));
        Notification::assertSentTo($client, ClientInvitation::class);

        $project = Project::first();
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($lead->id, $project->lead_expert_id);
        $this->assertSame('in_progress', $project->status);
        $this->assertSame(['Audit'], $project->milestones->pluck('title')->all());
        $this->assertSame('manual', QuoteRequest::first()->source);
        $this->assertSame('accepted', Quotation::first()->status);
        $this->assertSame('PKR', Quotation::first()->currency);
        $this->assertSame(30, Quotation::first()->estimated_days);

        // The client sees it in their own project list.
        $this->actingAs($client)->get(route('projects.show', $project))->assertOk();
    }

    public function test_internal_project_has_no_client_and_staff_without_permission_cannot_create_one(): void
    {
        $service = Service::create(['name' => 'Web', 'slug' => 'web']);

        $this->actingAs($this->owner)->post(route('projects.store'), [
            'title' => 'Undek website v3', 'service_id' => $service->id, 'client_mode' => 'internal',
        ])->assertSessionHasNoErrors();
        $this->assertNull(Project::first()->client_id);

        $staff = $this->person('staff', 'web-development');
        $this->actingAs($staff)->get(route('projects.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('projects.store'), ['title' => 'X', 'service_id' => $service->id, 'client_mode' => 'internal'])->assertForbidden();
    }

    public function test_existing_client_must_actually_be_a_client(): void
    {
        $service = Service::create(['name' => 'Web', 'slug' => 'web']);
        $staff = $this->person('staff');

        $this->actingAs($this->owner)->post(route('projects.store'), [
            'title' => 'X', 'service_id' => $service->id, 'client_mode' => 'existing', 'client_id' => $staff->id,
        ])->assertStatus(422);
    }

    // ── Public forms & headers ──────────────────────────────────────────

    public function test_quote_form_honeypot_and_rate_limit(): void
    {
        $service = Service::create(['name' => 'Web', 'slug' => 'web', 'is_active' => true]);
        $form = ['service_id' => $service->id, 'title' => 'Site', 'description' => 'Need one', 'guest_name' => 'G', 'guest_email' => 'g@gmail.com'];

        $this->post('/quote', $form + ['website' => 'http://spam.example'])->assertSessionHasErrors('website');
        $this->assertSame(0, QuoteRequest::count());

        foreach (range(1, 4) as $i) {
            $this->post('/quote', $form)->assertRedirect();
        }
        $this->post('/quote', $form)->assertStatus(429);
        $this->assertSame(4, QuoteRequest::count());
    }

    public function test_failed_new_offer_does_not_cancel_the_live_one(): void
    {
        $specialist = $this->person('staff', 'sales');
        $client = $this->person('client');
        $service = Service::create(['name' => 'Web', 'slug' => 'web']);
        $quote = QuoteRequest::create(['service_id' => $service->id, 'client_id' => $client->id, 'title' => 'T', 'description' => 'D', 'assigned_to' => $specialist->id]);
        $live = Quotation::create(['quote_request_id' => $quote->id, 'specialist_id' => $specialist->id, 'proposed_by_id' => $specialist->id, 'budget' => 100, 'estimated_days' => 5, 'status' => 'sent']);

        $this->actingAs($specialist)->post(route('quotations.store', $quote), ['budget' => '', 'estimated_days' => 3])
            ->assertSessionHasErrors('budget');

        $this->assertSame('sent', $live->fresh()->status);
    }

    public function test_pages_send_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_no_default_owner_login_is_seeded_outside_development(): void
    {
        User::where('email', 'ceo@undek.com')->delete();
        $this->app['env'] = 'production';

        (new RolesAndPermissionsSeeder)->run();

        $this->assertFalse(User::where('email', 'ceo@undek.com')->exists());
    }
}
