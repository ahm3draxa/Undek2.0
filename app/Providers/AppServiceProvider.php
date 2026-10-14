<?php

namespace App\Providers;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
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

        Gate::define('manage-marketing', function (User $user) {
            return $user->hasAnyRole(['ceo', 'admin'])
                || $user->skills()->whereHas('category', fn ($q) => $q->where('name', 'Marketing'))->exists();
        });
    }
}
