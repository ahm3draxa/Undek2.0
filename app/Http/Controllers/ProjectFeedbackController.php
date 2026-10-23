<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectFeedbackController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();

        abort_unless(in_array($project->status, ['completed', 'delivered'], true), 422, 'Feedback can only be left once a project is completed or delivered.');

        $audience = match (true) {
            $project->client_id === $user->id => ProjectFeedback::CLIENT_TO_UNDEK,
            $user->can('manage all work') || $project->lead_expert_id === $user->id => ProjectFeedback::UNDEK_TO_CLIENT,
            default => null,
        };

        abort_if($audience === null, 403);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        ProjectFeedback::updateOrCreate(
            ['project_id' => $project->id, 'audience' => $audience],
            ['author_id' => $user->id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        return redirect()->route('projects.show', $project)->with('status', 'Feedback saved. Thank you!');
    }
}
