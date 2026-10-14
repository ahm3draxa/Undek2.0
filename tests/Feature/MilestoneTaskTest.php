<?php

namespace Tests\Feature;

use App\Models\EmployeeProfile;
use App\Models\Milestone;
use App\Models\MilestoneSubmissionFile;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\TaskActivity;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MilestoneTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $lead;

    private User $supervisor;

    private User $intern;

    private User $otherIntern;

    private Project $project;

    private Milestone $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');

        $this->client = $this->userWithRole('client');
        $this->lead = $this->userWithRole('expert');
        $this->supervisor = $this->userWithRole('employee');
        $this->intern = $this->userWithRole('intern');
        $this->otherIntern = $this->userWithRole('intern');

        // The intern reports to the supervisor; the other intern reports to nobody here.
        EmployeeProfile::create(['user_id' => $this->supervisor->id]);
        EmployeeProfile::create(['user_id' => $this->intern->id, 'manager_id' => $this->supervisor->id, 'employment_type' => 'intern']);
        EmployeeProfile::create(['user_id' => $this->otherIntern->id, 'employment_type' => 'intern']);

        $service = Service::create(['name' => 'Design', 'slug' => 'design']);
        $quote = QuoteRequest::create(['service_id' => $service->id, 'client_id' => $this->client->id, 'title' => 'Brand', 'description' => 'Logo']);
        $quotation = Quotation::create(['quote_request_id' => $quote->id, 'specialist_id' => $this->lead->id, 'budget' => 100, 'estimated_days' => 5, 'status' => 'accepted']);

        $this->project = Project::create([
            'quote_request_id' => $quote->id,
            'quotation_id' => $quotation->id,
            'client_id' => $this->client->id,
            'lead_expert_id' => $this->lead->id,
            'title' => 'Brand refresh',
            'status' => 'in_progress',
        ]);

        foreach ([$this->supervisor, $this->intern, $this->otherIntern] as $member) {
            $this->project->teamMembers()->create(['user_id' => $member->id]);
        }

        $this->milestone = $this->project->milestones()->create(['title' => 'Logo drafts', 'status' => 'pending', 'sort_order' => 1]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function assign(User $as, ?User $to)
    {
        return $this->actingAs($as)->patch(route('milestones.assign', [$this->project, $this->milestone]), ['assigned_to' => $to?->id]);
    }

    public function test_lead_can_assign_anyone_on_the_project_and_assignee_is_notified(): void
    {
        Notification::fake();

        $this->assign($this->lead, $this->otherIntern)->assertRedirect();

        $this->milestone->refresh();
        $this->assertSame($this->otherIntern->id, $this->milestone->assigned_to);
        $this->assertSame($this->lead->id, $this->milestone->assigned_by);
        Notification::assertSentTo($this->otherIntern, TaskActivity::class);
    }

    public function test_supervisor_can_assign_their_own_intern_but_not_someone_elses(): void
    {
        $this->assign($this->supervisor, $this->intern)->assertRedirect();
        $this->assertSame($this->intern->id, $this->milestone->fresh()->assigned_to);

        $this->assign($this->supervisor, $this->otherIntern)->assertForbidden();
        $this->assertSame($this->intern->id, $this->milestone->fresh()->assigned_to);
    }

    public function test_supervisor_cannot_take_a_task_away_from_someone_they_do_not_supervise(): void
    {
        $this->assign($this->lead, $this->otherIntern);

        $this->assign($this->supervisor, $this->intern)->assertForbidden();
        $this->assertSame($this->otherIntern->id, $this->milestone->fresh()->assigned_to);
    }

    public function test_intern_cannot_assign_tasks(): void
    {
        $this->assign($this->intern, $this->otherIntern)->assertForbidden();
    }

    public function test_assignee_submits_files_and_marks_the_task_done(): void
    {
        $this->assign($this->supervisor, $this->intern);
        Notification::fake();

        $this->actingAs($this->intern)->post(route('milestones.submit', [$this->project, $this->milestone]), [
            'note' => 'Three logo options attached.',
            'mark_done' => '1',
            'files' => [UploadedFile::fake()->createWithContent('options.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->milestone->refresh();
        $this->assertSame('completed', $this->milestone->status);
        $this->assertNotNull($this->milestone->completed_at);

        $submission = $this->milestone->submissions()->with('files')->first();
        $this->assertSame('Three logo options attached.', $submission->note);
        $this->assertTrue($submission->marked_done);
        $this->assertCount(1, $submission->files);
        Storage::disk('local')->assertExists($submission->files->first()->path);

        // Whoever handed out the task and the project lead both hear about it.
        Notification::assertSentTo([$this->supervisor, $this->lead], TaskActivity::class);
        Notification::assertNotSentTo($this->intern, TaskActivity::class);
    }

    public function test_submission_needs_a_note_or_a_file(): void
    {
        $this->assign($this->lead, $this->intern);

        $this->actingAs($this->intern)->post(route('milestones.submit', [$this->project, $this->milestone]), [])
            ->assertSessionHasErrors('note');
    }

    public function test_someone_not_on_the_task_cannot_submit(): void
    {
        $this->assign($this->lead, $this->intern);

        $this->actingAs($this->otherIntern)->post(route('milestones.submit', [$this->project, $this->milestone]), ['note' => 'Hi'])
            ->assertForbidden();
    }

    public function test_submitted_files_are_visible_to_the_team_but_not_the_client(): void
    {
        $this->assign($this->lead, $this->intern);
        $this->actingAs($this->intern)->post(route('milestones.submit', [$this->project, $this->milestone]), [
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'Some notes')],
        ])->assertSessionHasNoErrors();

        $file = MilestoneSubmissionFile::first();
        $url = route('milestones.files.show', [$this->project, $this->milestone, $file]);

        $this->actingAs($this->lead)->get($url)->assertOk();
        $this->actingAs($this->supervisor)->get($url)->assertOk();
        $this->actingAs($this->client)->get($url)->assertForbidden();
    }

    public function test_my_tasks_lists_assigned_and_handed_out_work(): void
    {
        $this->assign($this->supervisor, $this->intern);

        $this->actingAs($this->intern)->get(route('tasks.index'))->assertOk()->assertSee('Logo drafts')->assertSee('Assigned to me (1)');
        $this->actingAs($this->supervisor)->get(route('tasks.index'))->assertOk()->assertSee('Logo drafts')->assertSee('Assigned by me (1)');
    }
}
