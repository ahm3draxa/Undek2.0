<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeManage($request, $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['sort_order'] = $project->milestones()->max('sort_order') + 1;
        $data['status'] = 'pending';

        $project->milestones()->create($data);

        return back()->with('status', 'Milestone added.');
    }

    public function update(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $this->authorizeManage($request, $project);
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed'],
        ]);

        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;

        $milestone->update($data);

        return back()->with('status', 'Milestone updated.');
    }

    public function destroy(Request $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $this->authorizeManage($request, $project);
        abort_unless($milestone->project_id === $project->id, 404);

        $milestone->delete();

        return back()->with('status', 'Milestone removed.');
    }

    private function authorizeManage(Request $request, Project $project): void
    {
        $user = $request->user();
        abort_unless(
            $user->hasAnyRole(['ceo', 'admin']) || $project->lead_expert_id === $user->id,
            403
        );
    }
}
