<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated account loses access on its very next request, not just at
 * its next login — an already-open session or "remember me" cookie must not
 * outlive the deactivation.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Strictly false: the column defaults to active, so only an explicit
        // deactivation counts.
        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'This account has been deactivated.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'This account has been deactivated. Contact Undek if you think this is a mistake.']);
        }

        return $next($request);
    }
}
