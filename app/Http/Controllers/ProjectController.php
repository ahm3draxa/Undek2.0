<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersBySkill;
use App\Http\Controllers\Concerns\NotifiesThreadParticipants;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ClientInvitation;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use FiltersBySkill, NotifiesThreadParticipants;

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Project::with(['client', 'leadExpert', 'milestones'])->latest();

        if (! $user->can('manage all work')) {
            $query->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('lead_expert_id', $user->id)
                    ->orWhereHas('teamMembers', fn ($m) => $m->where('user_id', $user->id));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'pending' => (clone $query)->where('status', 'pending_assignment')->count(),
        ];

        $projects = $query->paginate(15);

        return view('projects.index', compact('projects', 'stats'));
    }

    /**
     * Set up a project directly — for a client who came by phone or WhatsApp,
     * repeat work for an existing client, or an internal Undek project.
     */
    public function create(): View
    {
        $services = Service::with('department')->orderBy('name')->get();
        $clients = User::role(Organization::CLIENT)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        [$leadCandidates] = $this->candidatesForSkills('lead', collect());

        return view('projects.create', compact('services', 'clients', 'leadCandidates'));
    }

    /**
     * Behind the scenes this records the same quote request → accepted
     * quotation → project chain as the website flow (marked source 'manual'),
     * so reports, client project lists and permissions treat it like any
     * other project.
     */
    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'service_id' => ['required', 'exists:services,id'],
            'client_mode' => ['required', 'in:existing,new,internal'],
            'client_id' => ['required_if:client_mode,existing', 'nullable', 'exists:users,id'],
            'new_client_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:255'],
            'new_client_email' => ['required_if:client_mode,new', 'nullable', 'string', 'lowercase', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'lead_expert_id' => ['nullable', 'exists:users,id'],
            'milestones' => ['array', 'max:30'],
            'milestones.*.title' => ['nullable', 'string', 'max:255'],
            'milestones.*.due_date' => ['nullable', 'date'],
        ]);

        if ($data['client_mode'] === 'existing') {
            abort_unless(User::find($data['client_id'])?->hasRole(Organization::CLIENT), 422, 'Choose an existing client account.');
        }

        $this->ensureCanLead($data['lead_expert_id'] ?? null);

        $startDate = Carbon::parse($data['start_date'] ?? now())->startOfDay();
        $dueDate = isset($data['due_date']) ? Carbon::parse($data['due_date'])->startOfDay() : null;
        $invitee = null;

        $project = DB::transaction(function () use ($data, $actor, $startDate, $dueDate, &$invitee) {
            $clientId = match ($data['client_mode']) {
                'existing' => (int) $data['client_id'],
                'internal' => null,
                'new' => tap(User::create([
                    'name' => $data['new_client_name'],
                    'email' => $data['new_client_email'],
                    // Unusable until they choose their own through the invitation.
                    'password' => Hash::make(Str::random(40)),
                    'is_active' => true,
                ]), function (User $user) use (&$invitee) {
                    // Staff vouch for the address, and the invitation link proves
                    // it too. Not mass-assignable, so set explicitly.
                    $user->forceFill(['email_verified_at' => now()])->save();
                    $user->assignRole(Organization::CLIENT);
                    $invitee = $user;
                })->id,
            };

            $quoteRequest = QuoteRequest::create([
                'service_id' => $data['service_id'],
                'client_id' => $clientId,
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'status' => 'converted',
                'source' => 'manual',
                'assigned_to' => $actor->id,
            ]);

            $quotation = Quotation::create([
                'quote_request_id' => $quoteRequest->id,
                'specialist_id' => $actor->id,
                'proposed_by_id' => $actor->id,
                'budget' => $data['budget'] ?? 0,
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'estimated_days' => $dueDate ? max(1, (int) $startDate->diffInDays($dueDate)) : 1,
                'delivery_date' => $dueDate?->toDateString(),
                'notes' => 'Set up manually by '.$actor->name.'.',
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            $project = Project::create([
                'quote_request_id' => $quoteRequest->id,
                'quotation_id' => $quotation->id,
                'client_id' => $clientId,
                'lead_expert_id' => $data['lead_expert_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => ! empty($data['lead_expert_id']) ? 'in_progress' : 'pending_assignment',
                'start_date' => $startDate->toDateString(),
                'due_date' => $dueDate?->toDateString(),
            ]);

            foreach (array_values($data['milestones'] ?? []) as $index => $milestone) {
                if (trim($milestone['title'] ?? '') !== '') {
                    $project->milestones()->create([
                        'title' => $milestone['title'],
                        'due_date' => $milestone['due_date'] ?? null,
                        'status' => 'pending',
                        'sort_order' => $index,
                    ]);
                }
            }

            return $project;
        });

        $this->postToThread($project, $actor, $project->client_id
            ? "{$actor->name} set up this project."
            : "{$actor->name} set up this internal project.", 'event', 'team');

        // Sent after the transaction so a mail failure can't undo the project.
        $invitee?->notify(new ClientInvitation($project->title));

        return redirect()->route('projects.show', $project)->with('status', $invitee
            ? "Project created. {$invitee->email} has been emailed a link to set their password."
            : 'Project created.');
    }

    public function show(Request $request, Project $project): View
    {
        $user = $request->user();
        $allowed = $user->can('manage all work')
            || $project->client_id === $user->id
            || $project->lead_expert_id === $user->id
            || $project->teamMembers()->where('user_id', $user->id)->exists();

        abort_unless($allowed, 403);

        $project->load(['client', 'leadExpert', 'teamMembers.user.employeeProfile', 'milestones.assignedTo', 'milestones.assignedBy', 'milestones.submissions.user', 'milestones.submissions.files','contributions.user', 'quotation', 'quoteRequest.service', 'quoteRequest.skills', 'feedbacks.author']);

        // Interns are included here (not just expert/employee) so a specialist or lead
        // can pull in their own interns for department-specific work — e.g. design
        // assets vs. development integration — and then delegate individual
        // milestones to them once they're on the team.
        [$experts, $skillMatched, $expertEscalatedTo] = $project->quoteRequest
            ? $this->candidatesForQuoteRequest('team', $project->quoteRequest)
            : $this->candidatesForSkills('team', collect());

        // The lead comes from people who can actually lead projects (delivery
        // teams, no interns); the team picker above is any staff member.
        [$leadCandidates, $leadSkillMatched, $leadEscalatedTo] = $project->quoteRequest
            ? $this->candidatesForQuoteRequest('lead', $project->quoteRequest)
            : $this->candidatesForSkills('lead', collect());

        return view('projects.show', compact('project', 'experts', 'skillMatched', 'expertEscalatedTo', 'leadCandidates', 'leadSkillMatched', 'leadEscalatedTo'));
    }

    /**
     * CEO/admin finalizes an accepted quotation into a live project.
     */
    public function finalize(Request $request, Quotation $quotation): RedirectResponse
    {
        abort_unless($quotation->status === 'accepted', 422, 'Quotation must be accepted before finalizing a project.');
        abort_if($quotation->project()->exists(), 422, 'A project already exists for this quotation.');

        $data = $request->validate([
            'lead_expert_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'milestones' => ['array'],
            'milestones.*.title' => ['nullable', 'string', 'max:255'],
            'milestones.*.due_date' => ['nullable', 'date'],
        ]);

        $this->ensureCanLead($data['lead_expert_id'] ?? null);

        $quoteRequest = $quotation->quoteRequest;

        $project = Project::create([
            'quote_request_id' => $quoteRequest->id,
            'quotation_id' => $quotation->id,
            'client_id' => $quoteRequest->client_id,
            'lead_expert_id' => $data['lead_expert_id'] ?? null,
            'title' => $quoteRequest->title,
            'description' => $quoteRequest->description,
            'status' => 'pending_assignment',
            'start_date' => now()->toDateString(),
            'due_date' => $data['due_date'] ?? null,
        ]);

        foreach (array_values($data['milestones'] ?? []) as $index => $milestone) {
            if (trim($milestone['title'] ?? '') === '') {
                continue;
            }

            $project->milestones()->create([
                'title' => $milestone['title'],
                'due_date' => $milestone['due_date'] ?? null,
                'status' => 'pending',
                'sort_order' => $index,
            ]);
        }

        // The specialist who worked this deal is carried over onto the execution
        // team automatically — otherwise the handoff from sales to delivery
        // silently drops the one person with the most context on what the client
        // actually wants. Skipped if they're also being made the lead, since
        // they're already reachable on the project either way.
        if ($quotation->specialist_id && (int) $quotation->specialist_id !== (int) $project->lead_expert_id) {
            $project->teamMembers()->firstOrCreate(
                ['user_id' => $quotation->specialist_id],
                ['role_on_project' => 'Specialist', 'added_by' => $request->user()->id]
            );

            $this->postToThread($project, $request->user(), sprintf(
                '%s carried over %s from the original quotation as a team member.',
                $request->user()->name, $quotation->specialist->name
            ), 'event', 'team');
        }

        $quoteRequest->update(['status' => 'converted']);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage all work') || $project->lead_expert_id === $user->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:pending_assignment,in_progress,completed,delivered,cancelled'],
            'lead_expert_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $this->ensureCanLead($data['lead_expert_id'] ?? null);

        $project->update($data + [
            'delivered_at' => $data['status'] === 'delivered' ? now() : $project->delivered_at,
        ]);

        return back()->with('status', 'Project updated.');
    }

    /**
     * A project lead must be an active staff member who can lead projects —
     * not a client, and not someone deactivated.
     */
    private function ensureCanLead(?int $userId): void
    {
        if (! $userId) {
            return;
        }

        $lead = User::find($userId);

        abort_unless(
            $lead && $lead->is_active && $lead->isStaff() && ! $lead->isIntern() && ($lead->can('lead projects') || $lead->can('manage all work')),
            422,
            'The project lead must be an active staff member who can lead projects.'
        );
    }
}
