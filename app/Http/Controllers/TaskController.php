<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Milestones across every project, from one person's point of view: the
     * ones assigned to them, and the ones they've handed out to others.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $showDone = $request->boolean('done');

        $base = fn () => Milestone::with(['project:id,title', 'assignedTo:id,name', 'assignedBy:id,name'])
            ->withCount('submissions')
            ->when(! $showDone, fn ($q) => $q->where('status', '!=', 'completed'))
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest('id');

        $assignedToMe = $base()->where('assigned_to', $user->id)->get();
        $assignedByMe = $base()->where('assigned_by', $user->id)->where('assigned_to', '!=', $user->id)->get();

        return view('tasks.index', compact('assignedToMe', 'assignedByMe', 'showDone'));
    }
}
