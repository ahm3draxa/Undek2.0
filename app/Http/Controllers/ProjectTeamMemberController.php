<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTeamMemberController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage all work') || $project->lead_expert_id === $user->id, 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_on_project' => ['nullable', 'in:'.implode(',', Organization::PROJECT_ROLES)],
        ]);

        abort_unless(User::find($data['user_id'])?->isStaff(), 422, 'Only Undek staff can be added to a project team.');

        $project->teamMembers()->firstOrCreate(
            ['user_id' => $data['user_id']],
            ['role_on_project' => $data['role_on_project'] ?? 'Member', 'added_by' => $user->id]
        );

        return back()->with('status', 'Team member added.');
    }

    public function destroy(Request $request, Project $project, int $member): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage all work') || $project->lead_expert_id === $user->id, 403);

        $project->teamMembers()->whereKey($member)->delete();

        return back()->with('status', 'Team member removed.');
    }
}
