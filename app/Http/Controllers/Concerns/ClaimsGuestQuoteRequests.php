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
     */
    protected function claimGuestQuoteRequests(User $user): void
    {
        QuoteRequest::whereNull('client_id')
            ->whereRaw('LOWER(guest_email) = ?', [strtolower($user->email)])
            ->update(['client_id' => $user->id]);
    }
}
