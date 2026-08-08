<?php

namespace App\Http\Controllers\Concerns;

use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

trait FiltersBySkill
{
    /**
     * Users holding any of the given roles, narrowed down to whoever has at least
     * one of the given skills. Falls back to the full role-based list when there
     * are no skills to match against, or nobody happens to match — so assignment
     * never dead-ends just because skills haven't been set up.
     *
     * @return array{0: Collection, 1: bool} the candidates, and whether skill-matching was actually applied
     */
    protected function candidatesForSkills(array $roles, Collection $skillIds): array
    {
        $base = User::role($roles)->with('skills')->orderBy('name');

        if ($skillIds->isEmpty()) {
            return [$base->get(), false];
        }

        $matched = (clone $base)->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $skillIds))->get();

        return $matched->isNotEmpty() ? [$matched, true] : [$base->get(), false];
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
