<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait EnforcesSingleSession
{
    /**
     * One active session per account — logging in from a new device or
     * browser invalidates whatever session(s) that account already had
     * elsewhere. Call after session()->regenerate(), so the current
     * session's own (new) ID is excluded from the sweep, and after
     * Auth::login(), so $remember matches what that call just did.
     *
     * Relies on the sessions table (SESSION_DRIVER=database) tracking
     * user_id per row; the session sweep is a no-op on any other driver.
     */
    protected function invalidateOtherSessions(Request $request, User $user, bool $remember = false): void
    {
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // Auth::login() only rotates remember_token when $remember is true.
        // If this login didn't check "remember me", a previously remembered
        // device's cookie would still validate against the old token and
        // silently re-establish a session — rotating it here forces that
        // device back through the login form, where it would be caught by
        // the same enforcement.
        if (! $remember) {
            $user->setRememberToken(Str::random(60));
            $user->save();
        }
    }
}
