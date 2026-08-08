<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectTeamMemberController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['ceo', 'admin']) || $project->lead_expert_id === $user->id, 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_on_project' => ['nullable', 'string', 'max:255'],
        ]);

        $project->teamMembers()->firstOrCreate(
            ['user_id' => $data['user_id']],
            ['role_on_project' => $data['role_on_project'] ?? null, 'added_by' => $user->id]
        );

        return back()->with('status', 'Team member added.');
    }

    public function destroy(Request $request, Project $project, int $member): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['ceo', 'admin']) || $project->lead_expert_id === $user->id, 403);

        $project->teamMembers()->whereKey($member)->delete();

        return back()->with('status', 'Team member removed.');
    }
}
