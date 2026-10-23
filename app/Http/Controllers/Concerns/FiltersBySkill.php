<?php

namespace App\Http\Controllers\Concerns;

use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who to suggest for a piece of work. Three pools, each defined by what the
 * person is allowed to do rather than by a role name:
 *
 *   'quote' — staff who can price quote requests ('send quotation', i.e. Sales)
 *   'lead'  — staff who can lead projects ('lead projects', i.e. delivery teams), not interns
 *   'team'  — any staff member
 *
 * Within a pool, people with a matching skill are suggested first — skills
 * are what match someone to work, whichever department they sit in.
 */
trait FiltersBySkill
{
    /**
     * When a pool has nobody in it: admins next, then owners — who always
     * exist (the app keeps at least one owner). The single answer to "what if
     * the right person isn't available" for every assignment dropdown.
     */
    private const ESCALATION_CHAIN = [Organization::ADMIN, Organization::OWNER];

    /**
     * @return array{0: Collection, 1: bool, 2: ?string} candidates, whether skill-matching was
     *         applied, and which role we had to escalate to (null if the pool had people in it)
     */
    protected function candidatesForSkills(string $pool, Collection $skillIds, ?Service $service = null): array
    {
        [$query, $escalatedTo] = $this->resolvePool($pool);

        $base = (clone $query)->with(['skills', 'employeeProfile.position'])->orderBy('name')->get();
        $matched = $skillIds->isEmpty()
            ? collect()
            : (clone $query)->with(['skills', 'employeeProfile.position'])
                ->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $skillIds))
                ->orderBy('name')->get();

        $candidates = $matched->isNotEmpty() ? $matched : $base;

        // The service's own team first, everyone else after.
        if ($service?->department_id) {
            $candidates = $candidates->sortBy(fn (User $user) => $user->departments()->contains('id', $service->department_id) ? 0 : 1)->values();
        }

        return [$candidates, $matched->isNotEmpty(), $escalatedTo];
    }

    protected function candidatesForService(string $pool, ?Service $service): array
    {
        return $this->candidatesForSkills($pool, $service?->skills->pluck('id') ?? collect(), $service);
    }

    /**
     * Sourced from whatever the client actually chose on their request —
     * falling back to the service's default skills if they didn't pick any.
     */
    protected function candidatesForQuoteRequest(string $pool, QuoteRequest $quoteRequest): array
    {
        $skillIds = $quoteRequest->skills->isNotEmpty()
            ? $quoteRequest->skills->pluck('id')
            : ($quoteRequest->service?->skills->pluck('id') ?? collect());

        return $this->candidatesForSkills($pool, $skillIds, $quoteRequest->service);
    }

    /**
     * @return array{0: Builder, 1: ?string}
     */
    private function resolvePool(string $pool): array
    {
        $query = $this->poolQuery($pool);

        if ((clone $query)->exists()) {
            return [$query, null];
        }

        foreach (self::ESCALATION_CHAIN as $role) {
            $fallback = User::role($role)->where('is_active', true);

            if ((clone $fallback)->exists()) {
                return [$fallback, $role];
            }
        }

        return [User::role(Organization::OWNER), Organization::OWNER];
    }

    private function poolQuery(string $pool): Builder
    {
        $staff = User::role(Organization::STAFF)->where('is_active', true);

        return match ($pool) {
            'quote' => $staff->permission('send quotation'),
            'lead' => $staff->permission('lead projects')
                ->whereDoesntHave('employeeProfile', fn ($q) => $q->where('employment_type', 'intern')),
            'team' => $staff,
        };
    }
}
