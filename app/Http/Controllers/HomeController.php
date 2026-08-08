<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();

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

        return view('welcome', compact('services', 'team'));
    }

    private function buildTeamTree(Collection $byManager, int|string $key): Collection
    {
        return $byManager->get($key, collect())
            ->sortBy('name')
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
