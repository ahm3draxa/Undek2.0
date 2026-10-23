<?php

namespace App\Providers;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force generated URLs (assets, routes, redirects) to https:// whenever the
        // request actually arrived securely — directly, or via a proxy that terminates
        // TLS and reports it through X-Forwarded-Proto. Unlike a blanket force, this
        // naturally stays off under `php artisan serve` (no TLS, isSecure() is false)
        // without needing a separate SAPI check, and won't fight a genuine plain-http
        // request that hasn't been upgraded yet by RedirectToHttps.
        if (request()->isSecure() || request()->server->get('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }

        // Guest quote requests are only handed to an account once its email is
        // proven — see QuoteRequest::claimGuestRequestsFor().
        Event::listen(Verified::class, fn (Verified $event) => QuoteRequest::claimGuestRequestsFor($event->user));

        // Anonymous forms (quote requests with uploads, registration): enough for
        // a real person retrying, far too little to flood storage or the
        // hourly database allowance.
        RateLimiter::for('public-forms', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);

        // Owners can do everything, including anything added after the role's
        // permissions were last synced.
        Gate::before(fn (User $user) => $user->isOwner() ? true : null);

        // Whether someone can open at least one shared mailbox — each mailbox
        // has its own "view <mailbox> inbox" permission (see InboxMessage::mailboxesFor).
        Gate::define('view any inbox', fn (User $user) => collect(config('inbox.mailboxes'))
            ->keys()->contains(fn ($mailbox) => $user->can("view $mailbox inbox")));
    }
}
