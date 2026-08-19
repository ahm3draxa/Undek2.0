<?php

namespace App\Http\Controllers\Concerns;

use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

trait FiltersBySkill
{
    /**
     * The escalation chain used whenever a functional role (specialist, expert, employee...)
     * has nobody in it: try Admin next, then CEO — who always exists (the app enforces at
     * least one CEO account at all times). This is the single place that answers "what
     * happens if the right rank isn't available" for every assignment dropdown in the app,
     * instead of each controller inventing its own ad-hoc fallback list.
     */
    private const ESCALATION_CHAIN = ['admin', 'ceo'];

    /**
     * Users holding any of the given roles, narrowed down to whoever has at least one of the
     * given skills. If nobody holds the requested roles at all, escalates up the management
     * chain (Admin, then CEO) rather than returning an empty, dead-end list. Skill-matching
     * itself also falls back to the full (possibly escalated) role pool if nobody matches.
     *
     * @return array{0: Collection, 1: bool, 2: ?string} candidates, whether skill-matching was
     *         applied, and which role we had to escalate to (null if the requested roles had
     *         people in them already)
     */
    protected function candidatesForSkills(array $roles, Collection $skillIds): array
    {
        [$activeRoles, $escalatedTo] = $this->resolveRolePool($roles);

        $base = User::role($activeRoles)->with('skills')->orderBy('name')->get();

        if ($skillIds->isEmpty()) {
            return [$base, false, $escalatedTo];
        }

        $matched = User::role($activeRoles)->with('skills')
            ->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $skillIds))
            ->orderBy('name')->get();

        return $matched->isNotEmpty() ? [$matched, true, $escalatedTo] : [$base, false, $escalatedTo];
    }

    /**
     * Finds the first role tier (requested roles, then Admin, then CEO) that actually has
     * someone in it, so callers never hit a completely empty candidate list.
     *
     * @return array{0: array, 1: ?string}
     */
    private function resolveRolePool(array $roles): array
    {
        if (User::role($roles)->exists()) {
            return [$roles, null];
        }

        $tried = $roles;
        foreach (self::ESCALATION_CHAIN as $fallbackRole) {
            $tried = array_unique([...$tried, $fallbackRole]);

            if (User::role($tried)->exists()) {
                return [$tried, $fallbackRole];
            }
        }

        return [$tried, end(self::ESCALATION_CHAIN)];
    }

    /**
     * Same as candidatesForSkills, but sourced from a service's required skills.
     */
    protected function candidatesForService(array $roles, ?Service $service): array
    {
        return $this->candidatesForSkills($roles, $service?->skills->pluck('id') ?? collect());
    }

    /**
     * Same as candidatesForSkills, sourced from whatever the client actually chose
     * on their request — falling back to the service's default skills if they
     * didn't pick any.
     */
    protected function candidatesForQuoteRequest(array $roles, QuoteRequest $quoteRequest): array
    {
        $skillIds = $quoteRequest->skills->isNotEmpty()
            ? $quoteRequest->skills->pluck('id')
            : ($quoteRequest->service?->skills->pluck('id') ?? collect());

        return $this->candidatesForSkills($roles, $skillIds);
    }
}
