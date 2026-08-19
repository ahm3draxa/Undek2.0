<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesThreadParticipants;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    use NotifiesThreadParticipants;

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeManage($request, $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($data['assigned_to'])) {
            $this->ensureAssignable($project, (int) $data['assigned_to']);
        }

        $data['sort_order'] = $project->milestones()->max('sort_order') + 1;
        $data['status'] = 'pending';

        $milestone = $project->milestones()->create($data);

        // Client-facing: that a milestone exists and its timeline — no internal
        // staffing detail.
        $this->postToThread($project, $request->user(), sprintf(
            '%s added a milestone: "%s"%s.',
            $request->user()->name, $milestone->title,
            $milestone->due_date ? ' — due '.$milestone->due_date->format('M j, Y') : ''
        ), 'event');

        // Team-facing only: who's actually doing it, if assigned at creation.
        if ($milestone->assignedTo) {
            $this->postToThread($project, $request->user(), sprintf(
                '%s assigned the milestone "%s" to %s.',
                $request->user()->name, $milestone->title, $milestone->assignedTo->name
            ), 'event', 'team');
        }

        return back()->with('status', 'Milestone added.');
    }

    public function update(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $this->authorizeManage($request, $project, $milestone);

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed'],
        ]);

        $previousStatus = $milestone->status;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;

        $milestone->update($data);

        // Log every real transition, not just "completed" — otherwise reverting a
        // milestone (e.g. completed back to in-progress) leaves the chat showing
        // a "marked as complete" event that's no longer true, with nothing to
        // correct it.
        if ($data['status'] !== $previousStatus) {
            $label = fn (string $status) => str_replace('_', ' ', ucfirst($status));

            $this->postToThread($project, $request->user(), $data['status'] === 'completed'
                ? sprintf('%s marked the milestone "%s" as complete.', $request->user()->name, $milestone->title)
                : sprintf(
                    '%s changed the milestone "%s" from %s to %s.',
                    $request->user()->name, $milestone->title, $label($previousStatus), $label($data['status'])
                ), 'event');
        }

        return back()->with('status', 'Milestone updated.');
    }

    /**
     * Delegating a milestone to a specific person stays a lead-level decision
     * (not open to every team member with "manage milestones"), so it's kept
     * separate from the status-update authorization.
     */
    public function assign(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $this->authorizeLead($request, $project);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $newAssigneeId = $data['assigned_to'] ?? null;

        if ($newAssigneeId) {
            $this->ensureAssignable($project, (int) $newAssigneeId);
        }

        if ((int) $newAssigneeId === (int) $milestone->assigned_to) {
            return back();
        }

        $milestone->update(['assigned_to' => $newAssigneeId]);

        $newAssigneeName = $newAssigneeId ? $milestone->assignedTo()->first()?->name : null;

        // Staffing/assignment detail is internal — team channel only, never the
        // client-facing thread.
        $this->postToThread($project, $request->user(), $newAssigneeName
            ? sprintf('%s assigned the milestone "%s" to %s.', $request->user()->name, $milestone->title, $newAssigneeName)
            : sprintf('%s unassigned the milestone "%s".', $request->user()->name, $milestone->title), 'event', 'team');

        return back()->with('status', 'Milestone assignment updated.');
    }

    public function destroy(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $this->authorizeManage($request, $project);

        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    /**
     * CEO/admin always can. Otherwise this is a permission check, not a role
     * check — the 'manage milestones' permission (granted to the expert and
     * employee roles, or via a position) is what actually decides it, scoped
     * to people genuinely on the project (lead or team member). On top of
     * that, whoever a specific milestone is directly assigned to can always
     * manage that one milestone, even without the broader permission or
     * formal team membership — e.g. a specialist's intern pulled in just for
     * one piece of work.
     */
    private function authorizeManage(Request $request, Project $project, ?Milestone $milestone = null): void
    {
        $user = $request->user();

        if ($milestone && $milestone->assigned_to === $user->id) {
            return;
        }

        $isOnProject = $project->lead_expert_id === $user->id
            || $project->teamMembers()->where('user_id', $user->id)->exists();

        abort_unless(
            $user->hasAnyRole(['ceo', 'admin']) || ($isOnProject && $user->can('manage milestones')),
            403
        );
    }

    private function authorizeLead(Request $request, Project $project): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['ceo', 'admin']) || $project->lead_expert_id === $user->id,
            403
        );
    }

    /**
     * A milestone can only be delegated to someone genuinely reachable on this
     * project — the lead themselves or a current team member — so assignment
     * can't silently hand work to someone with no access to see or discuss it.
     */
    private function ensureAssignable(Project $project, int $userId): void
    {
        $isReachable = $project->lead_expert_id === $userId
            || $project->teamMembers()->where('user_id', $userId)->exists();

        abort_unless($isReachable, 422, 'That person needs to be added to the project team before a milestone can be assigned to them.');
    }
}
