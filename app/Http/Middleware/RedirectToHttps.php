<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToHttps
{
    /**
     * Force HTTPS in local dev, but only when the app is actually configured
     * for it (APP_URL uses https://) — otherwise this fights setups that
     * intentionally serve plain HTTP locally (e.g. an Apache vhost on a
     * plain HTTP port), redirecting them to a scheme nothing is listening on.
     *
     * Skipped under `php artisan serve` (PHP's built-in server), which has no
     * TLS support at all — redirecting to https:// there breaks the server
     * entirely ("Unsupported SSL request").
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUsesHttps = str_starts_with(config('app.url'), 'https://');

        if ($appUsesHttps && ! $request->secure() && app()->environment('local') && PHP_SAPI !== 'cli-server') {
            // 307 (not the default 302) so browsers preserve the HTTP method and body —
            // a 302 on a POST gets silently downgraded to a GET, dropping form data and
            // making the first click on any form look like it did nothing.
            return redirect()->to(preg_replace('/^http:/', 'https:', $request->fullUrl(), 1), 307);
        }

        return $next($request);
    }
}
