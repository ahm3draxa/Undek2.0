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
    /**
     * Everything on first load comes from this one request (one MySQL
     * connection); the stats then refresh every few minutes while the page is
     * actually being looked at.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $view = $this->viewFor($user);

        $myTasks = $user->isStaff()
            ? Milestone::with('project:id,title')
                ->where('assigned_to', $user->id)
                ->where('status', '!=', 'completed')
                ->orderByRaw('due_date IS NULL')->orderBy('due_date')
                ->limit(5)->get()
            : collect();

        $myQuotes = $user->can('send quotation')
            ? QuoteRequest::with('service:id,name')
                ->where('assigned_to', $user->id)
                ->whereIn('status', ['pending', 'quoted'])
                ->latest()->limit(5)->get()
            : collect();

        $attention = $user->can('manage all work') ? [
            'unassigned_quotes' => QuoteRequest::with('service:id,name')->where('status', 'pending')->whereNull('assigned_to')->latest()->limit(5)->get(),
            'projects_without_lead' => Project::whereNull('lead_expert_id')->whereIn('status', ['pending_assignment', 'in_progress'])->count(),
            'overdue_milestones' => Milestone::where('status', '!=', 'completed')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
        ] : null;

        $clientItems = $view === 'client'
            ? $user->clientProjects()->whereIn('status', ['pending_assignment', 'in_progress'])->latest()->limit(5)->get()
            : collect();

        return view('dashboard', [
            'view' => $view,
            'initialStats' => $this->statsFor($user, $view),
            'myTasks' => $myTasks,
            'myQuotes' => $myQuotes,
            'attention' => $attention,
            'clientItems' => $clientItems,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($this->statsFor($user, $this->viewFor($user)));
    }

    private function statsFor(User $user, string $view): array
    {
        $data = match ($view) {
            'executive' => $this->executiveStats($user),
            'specialist' => $this->specialistStats($user),
            'expert' => $this->expertStats($user),
            default => $this->clientStats($user),
        };

        return $data + ['updated_at' => now()->format('g:i A')];
    }

    /**
     * Which dashboard layout a user sees, based on what they're allowed to do.
     */
    private function viewFor(User $user): string
    {
        if ($user->can('manage all work')) {
            return 'executive';
        }

        if ($user->can('send quotation')) {
            return 'specialist';
        }

        if ($user->isStaff()) {
            return 'expert';
        }

        return 'client';
    }

    private function executiveStats(User $user): array
    {
        $stats = [
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
        ];

        // Money stays with whoever manages payroll (owners and the Management team).
        if ($user->can('manage payroll')) {
            $stats['payroll_this_month'] = (float) PayrollAdjustment::whereMonth('effective_date', now()->month)
                ->whereYear('effective_date', now()->year)
                ->sum('amount');
        }

        return $stats;
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
