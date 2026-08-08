<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersBySkill;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use FiltersBySkill;

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Project::with(['client', 'leadExpert', 'milestones'])->latest();

        if (! $user->hasAnyRole(['ceo', 'admin'])) {
            $query->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('lead_expert_id', $user->id)
                    ->orWhereHas('teamMembers', fn ($m) => $m->where('user_id', $user->id));
            });
        }

        $projects = $query->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, Project $project): View
    {
        $user = $request->user();
        $allowed = $user->hasAnyRole(['ceo', 'admin'])
            || $project->client_id === $user->id
            || $project->lead_expert_id === $user->id
            || $project->teamMembers()->where('user_id', $user->id)->exists();

        abort_unless($allowed, 403);

        $project->load(['client', 'leadExpert', 'teamMembers.user', 'milestones', 'contributions.user', 'quotation', 'quoteRequest.service', 'quoteRequest.skills', 'feedbacks.author']);

        [$experts, $skillMatched] = $project->quoteRequest
            ? $this->candidatesForQuoteRequest(['expert', 'employee'], $project->quoteRequest)
            : $this->candidatesForSkills(['expert', 'employee'], collect());

        return view('projects.show', compact('project', 'experts', 'skillMatched'));
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

        $quoteRequest->update(['status' => 'converted']);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['ceo', 'admin']) || $project->lead_expert_id === $user->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:pending_assignment,in_progress,completed,delivered,cancelled'],
            'lead_expert_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $project->update($data + [
            'delivered_at' => $data['status'] === 'delivered' ? now() : $project->delivered_at,
        ]);

        return back()->with('status', 'Project updated.');
    }
}
