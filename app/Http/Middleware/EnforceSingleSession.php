<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a session that is no longer the account's current one — because
 * the account signed in somewhere else, or an admin reset its password or
 * deactivated it. See EnforcesSingleSession.
 */
class EnforceSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->current_session_id && $user->current_session_id !== $request->session()->getId()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'You were signed out because this account signed in on another device.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('login')->with('status', $message);
        }

        return $next($request);
    }
}
