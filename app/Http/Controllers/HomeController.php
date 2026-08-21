<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFeedback;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();

        // Real client ratings only — best first, most recent as the tiebreaker.
        // Client names are deliberately not shown publicly; there's no consent
        // flag for featuring a client by name, so the service + quote speaks
        // for the work instead.
        $ratedProjects = Project::whereIn('status', ['completed', 'delivered'])
            ->with(['quoteRequest.service', 'feedbacks' => fn ($q) => $q->where('audience', ProjectFeedback::CLIENT_TO_UNDEK)])
            ->get()
            ->filter(fn (Project $project) => $project->feedbacks->isNotEmpty());

        $avgRating = $ratedProjects->isNotEmpty()
            ? round($ratedProjects->avg(fn (Project $p) => $p->feedbacks->first()->rating), 1)
            : null;
        $ratedProjectsCount = $ratedProjects->count();

        $deliveredProjects = $ratedProjects->sort(function (Project $a, Project $b) {
            $ratingCompare = $b->feedbacks->first()->rating <=> $a->feedbacks->first()->rating;

            return $ratingCompare !== 0
                ? $ratingCompare
                : ($b->delivered_at ?? $b->updated_at) <=> ($a->delivered_at ?? $a->updated_at);
        })->take(6)->values();

        $publicEmployees = User::whereHas('employeeProfile', fn ($q) => $q->where('is_public', true))
            ->with(['employeeProfile.department', 'employeeProfile.position', 'roles'])
            ->get();

        $publicIds = $publicEmployees->pluck('id');
        $byManager = $publicEmployees->groupBy(function ($user) use ($publicIds) {
            $managerId = $user->employeeProfile->manager_id;

            // Treat as a root if there's no manager, or the manager isn't shown publicly.
            return ($managerId && $publicIds->contains($managerId)) ? $managerId : 'root';
        });

        $team = $this->buildTeamTree($byManager, 'root');

        return view('welcome', compact('services', 'team', 'deliveredProjects', 'avgRating', 'ratedProjectsCount'));
    }

    private function buildTeamTree(Collection $byManager, int|string $key): Collection
    {
        return $byManager->get($key, collect())
            // CEOs first, then most senior position (rank) to least, name as the
            // final tiebreaker. Applies at every level so sub-teams read the same way.
            ->sortBy(fn ($user) => [
                $user->hasRole('ceo') ? 0 : 1,
                -($user->employeeProfile?->position?->rank ?? -1),
                $user->name,
            ])
            ->map(fn ($user) => [
                'user' => $user,
                'children' => $this->buildTeamTree($byManager, $user->id),
            ])
            ->values();
    }

    public function privacyPolicy(): View
    {
        return view('privacy-policy');
    }
}
