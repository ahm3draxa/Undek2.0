<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesThreadParticipants;
use App\Http\Controllers\Concerns\StreamsStoredFiles;
use App\Models\Milestone;
use App\Models\MilestoneSubmissionFile;
use App\Models\Project;
use App\Models\User;
use App\Notifications\TaskActivity;
use App\Rules\SafeUpload;
use App\Services\Uploads\SecureUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MilestoneController extends Controller
{
    use NotifiesThreadParticipants, StreamsStoredFiles;

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
            $this->ensureCanAssign($request->user(), $project, (int) $data['assigned_to']);
            $data['assigned_by'] = $request->user()->id;
            $data['assigned_at'] = now();
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

            $this->notifyAssignee($milestone, $request->user());
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

        $this->changeStatus($milestone, $data['status'], $request->user());

        return back()->with('status', 'Milestone updated.');
    }

    /**
     * Who may delegate is wider than who may edit: CEO/admin and the project
     * lead can hand a milestone to anyone on the project, and anyone on the
     * project can hand one to people they directly supervise (their interns
     * or reports). See Project::assignableBy().
     */
    public function assign(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $newAssigneeId = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;

        // Taking a milestone away from someone needs the same authority as
        // giving it to them — a supervisor can't reassign another team's work.
        if ($milestone->assigned_to) {
            $this->ensureCanAssign($request->user(), $project, (int) $milestone->assigned_to);
        }

        if ($newAssigneeId) {
            $this->ensureCanAssign($request->user(), $project, $newAssigneeId);
        } else {
            abort_if($project->assignableBy($request->user())->isEmpty(), 403);
        }

        if ($newAssigneeId === ($milestone->assigned_to ? (int) $milestone->assigned_to : null)) {
            return back();
        }

        $milestone->update([
            'assigned_to' => $newAssigneeId,
            'assigned_by' => $newAssigneeId ? $request->user()->id : null,
            'assigned_at' => $newAssigneeId ? now() : null,
        ]);

        $newAssigneeName = $newAssigneeId ? $milestone->assignedTo()->first()?->name : null;

        // Staffing/assignment detail is internal — team channel only, never the
        // client-facing thread.
        $this->postToThread($project, $request->user(), $newAssigneeName
            ? sprintf('%s assigned the milestone "%s" to %s.', $request->user()->name, $milestone->title, $newAssigneeName)
            : sprintf('%s unassigned the milestone "%s".', $request->user()->name, $milestone->title), 'event', 'team');

        if ($newAssigneeId) {
            $this->notifyAssignee($milestone->fresh('assignedTo', 'project'), $request->user());
        }

        return back()->with('status', 'Milestone assignment updated.');
    }

    /**
     * The assignee hands in their work — a note, files, or both — and can mark
     * the milestone done in the same step.
     */
    public function submit(Request $request, Project $project, Milestone $milestone, SecureUploader $uploader): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $this->authorizeManage($request, $project, $milestone);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:5000', 'required_without:files'],
            'mark_done' => ['sometimes', 'boolean'],
            ...SafeUpload::rulesFor('files', 'milestone_submission'),
        ], [
            'note.required_without' => 'Add a note or attach at least one file.',
        ]);

        $user = $request->user();
        $markDone = $request->boolean('mark_done');

        $storedFiles = collect($request->file('files', []))
            ->map(fn ($file) => $uploader->store($file, 'milestone-submissions'));

        $submission = DB::transaction(function () use ($milestone, $user, $data, $markDone, $storedFiles) {
            $submission = $milestone->submissions()->create([
                'user_id' => $user->id,
                'note' => $data['note'] ?? null,
                'marked_done' => $markDone,
            ]);

            $submission->files()->createMany($storedFiles->all());

            return $submission;
        });

        $fileCount = $storedFiles->count();
        $this->postToThread($project, $user, sprintf(
            '%s submitted work on "%s"%s.',
            $user->name, $milestone->title,
            $fileCount ? " ($fileCount file".($fileCount === 1 ? '' : 's').')' : ''
        ), 'event', 'team');

        if ($markDone) {
            $this->changeStatus($milestone, 'completed', $user);
        } elseif ($milestone->status === 'pending') {
            $this->changeStatus($milestone, 'in_progress', $user);
        }

        // Whoever handed out the task, plus the lead, hears about it directly.
        $recipients = collect([$milestone->assignedBy, $project->leadExpert])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === $user->id);

        Notification::send($recipients, new TaskActivity(
            $milestone,
            $user,
            ($markDone ? 'Completed' : 'Submitted work on').' "'.$milestone->title.'"'.($submission->note ? ': '.$submission->note : ''),
        ));

        return back()->with('status', $markDone ? 'Work submitted and task marked done.' : 'Work submitted.');
    }

    /**
     * Submitted files are internal: the project team, its lead, CEO/admin, and
     * whoever handed out or holds the task. Never the client.
     */
    public function submissionFile(Request $request, Project $project, Milestone $milestone, MilestoneSubmissionFile $file): StreamedResponse
    {
        abort_unless(
            $milestone->project_id === $project->id && $file->submission?->milestone_id === $milestone->id,
            404
        );

        $user = $request->user();
        abort_unless(
            $project->hasInternalAccess($user) || in_array($user->id, [$milestone->assigned_to, $milestone->assigned_by], true),
            403
        );

        return $this->streamStoredFile($file->disk, $file->path, $file->original_name, $file->mime_type);
    }

    public function destroy(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $this->authorizeManage($request, $project);

        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    private function changeStatus(Milestone $milestone, string $status, User $actor): void
    {
        $previousStatus = $milestone->status;

        $milestone->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        // Log every real transition, not just "completed" — otherwise reverting a
        // milestone (e.g. completed back to in-progress) leaves the chat showing
        // a "marked as complete" event that's no longer true, with nothing to
        // correct it.
        if ($status !== $previousStatus) {
            $label = fn (string $status) => str_replace('_', ' ', ucfirst($status));

            $this->postToThread($milestone->project, $actor, $status === 'completed'
                ? sprintf('%s marked the milestone "%s" as complete.', $actor->name, $milestone->title)
                : sprintf(
                    '%s changed the milestone "%s" from %s to %s.',
                    $actor->name, $milestone->title, $label($previousStatus), $label($status)
                ), 'event');
        }
    }

    private function notifyAssignee(Milestone $milestone, User $actor): void
    {
        if ($milestone->assignedTo && $milestone->assigned_to !== $actor->id) {
            $milestone->assignedTo->notify(new TaskActivity(
                $milestone,
                $actor,
                'Assigned you "'.$milestone->title.'"'.($milestone->due_date ? ' — due '.$milestone->due_date->format('M j') : ''),
            ));
        }
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

    /**
     * A milestone can only go to someone genuinely reachable on this project
     * (the lead or a current team member), and only from someone with
     * authority over them — see Project::assignableBy().
     */
    private function ensureCanAssign(User $assigner, Project $project, int $assigneeId): void
    {
        $isReachable = $project->lead_expert_id === $assigneeId
            || $project->teamMembers()->where('user_id', $assigneeId)->exists();

        abort_unless($isReachable, 422, 'That person needs to be added to the project team before a milestone can be assigned to them.');

        abort_unless(
            $project->assignableBy($assigner)->contains('id', $assigneeId),
            403,
            'You can only assign milestones to people you supervise on this project.'
        );
    }
}
