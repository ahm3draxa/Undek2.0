<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToHttps
{
    /**
     * Force HTTPS in local dev, where the browser's HTTP path to this app
     * has been unreliable (CSS/JS failing to load over plain HTTP on this machine).
     *
     * Skipped under `php artisan serve` (PHP's built-in server), which has no
     * TLS support at all — redirecting to https:// there breaks the server
     * entirely ("Unsupported SSL request").
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->secure() && app()->environment('local') && PHP_SAPI !== 'cli-server') {
            // 307 (not the default 302) so browsers preserve the HTTP method and body —
            // a 302 on a POST gets silently downgraded to a GET, dropping form data and
            // making the first click on any form look like it did nothing.
            return redirect()->to(preg_replace('/^http:/', 'https:', $request->fullUrl(), 1), 307);
        }

        return $next($request);
    }
}
