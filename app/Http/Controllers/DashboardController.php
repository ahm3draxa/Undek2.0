<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\PayrollAdjustment;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'view' => $this->viewFor($user),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = match ($this->viewFor($user)) {
            'executive' => $this->executiveStats(),
            'specialist' => $this->specialistStats($user),
            'expert' => $this->expertStats($user),
            default => $this->clientStats($user),
        };

        return response()->json($data + ['server_time' => now()->format('H:i:s')]);
    }

    /**
     * Which dashboard layout a user sees, based on their highest-privilege role.
     */
    private function viewFor(User $user): string
    {
        if ($user->hasAnyRole(['ceo', 'admin'])) {
            return 'executive';
        }

        if ($user->hasRole('specialist')) {
            return 'specialist';
        }

        if ($user->hasAnyRole(['expert', 'employee', 'intern'])) {
            return 'expert';
        }

        return 'client';
    }

    private function executiveStats(): array
    {
        return [
            'pending_quote_requests' => QuoteRequest::where('status', 'pending')->count(),
            'active_projects' => Project::whereIn('status', ['pending_assignment', 'in_progress'])->count(),
            'delivered_projects' => Project::where('status', 'delivered')->count(),
            'total_employees' => User::whereHas('employeeProfile')->count(),
            'total_clients' => User::role('client')->count(),
            'pipeline_value' => (float) Quotation::where('status', 'accepted')
                ->where(function ($q) {
                    $q->whereDoesntHave('project')
                        ->orWhereHas('project', fn ($p) => $p->whereIn('status', ['pending_assignment', 'in_progress']));
                })
                ->sum('budget'),
            'payroll_this_month' => (float) PayrollAdjustment::whereMonth('effective_date', now()->month)
                ->whereYear('effective_date', now()->year)
                ->sum('amount'),
        ];
    }

    private function specialistStats(User $user): array
    {
        return [
            'assigned_quote_requests' => QuoteRequest::where('assigned_to', $user->id)->where('status', 'pending')->count(),
            'quotations_sent' => $user->quotationsGiven()->count(),
            'quotations_accepted' => $user->quotationsGiven()->where('status', 'accepted')->count(),
            'quotations_pending_response' => $user->quotationsGiven()->where('status', 'sent')->count(),
        ];
    }

    private function expertStats(User $user): array
    {
        $projectIds = $user->ledProjects()->pluck('id')
            ->merge($user->projectMemberships()->pluck('project_id'))
            ->unique();

        return [
            'active_projects' => Project::whereIn('id', $projectIds)->whereIn('status', ['pending_assignment', 'in_progress'])->count(),
            'upcoming_milestones' => Milestone::whereIn('project_id', $projectIds)
                ->where('status', '!=', 'completed')
                ->whereNotNull('due_date')
                ->where('due_date', '>=', now()->toDateString())
                ->count(),
            'contributions_logged' => $user->contributions()->count(),
        ];
    }

    private function clientStats(User $user): array
    {
        return [
            'open_quote_requests' => $user->quoteRequests()->whereIn('status', ['pending', 'quoted'])->count(),
            'active_projects' => $user->clientProjects()->whereIn('status', ['pending_assignment', 'in_progress'])->count(),
            'delivered_projects' => $user->clientProjects()->where('status', 'delivered')->count(),
        ];
    }
}
