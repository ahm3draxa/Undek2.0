<?php

namespace App\Http\Controllers\Concerns;

use App\Models\QuoteRequest;
use App\Models\User;

trait ClaimsGuestQuoteRequests
{
    /**
     * Attach any guest-submitted quote requests matching this user's email to
     * their new account, so registering/logging in "picks up" work they
     * already started as a guest instead of leaving it orphaned.
     *
     * Only once the address is verified: otherwise anyone could register with
     * a client's email and read that client's quote requests. Accounts that
     * verify later pick their requests up then (see AppServiceProvider).
     */
    protected function claimGuestQuoteRequests(User $user): void
    {
        QuoteRequest::claimGuestRequestsFor($user);
    }
}
