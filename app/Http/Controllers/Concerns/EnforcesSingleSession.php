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
     * browser signs that account out everywhere else. Call after
     * session()->regenerate(), so the session recorded is the new one, and
     * after Auth::login(), so $remember matches what that call just did.
     *
     * The account records its current session ID; EnforceSingleSession
     * middleware signs out any other session on its next request. This works
     * with any session driver, so sessions can live in files.
     */
    protected function invalidateOtherSessions(Request $request, User $user, bool $remember = false): void
    {
        $user->forceFill(['current_session_id' => $request->session()->getId()]);

        // Auth::login() only rotates remember_token when $remember is true.
        // If this login didn't check "remember me", a previously remembered
        // device's cookie would still validate against the old token and
        // silently re-establish a session — rotating it here forces that
        // device back through the login form.
        if (! $remember) {
            $user->setRememberToken(Str::random(60));
        }

        $user->save();

        // Tidy up any database-stored sessions left from before the switch.
        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
    }
}
