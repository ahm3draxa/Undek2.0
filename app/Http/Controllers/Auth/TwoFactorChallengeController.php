<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ClaimsGuestQuoteRequests;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    use ClaimsGuestQuoteRequests;

    /**
     * Show the challenge form — only reachable mid-login, once credentials are
     * verified but before the session is fully authenticated.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.2fa.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($userId);
        $valid = false;

        if (! empty($data['code'])) {
            $valid = (new Google2FA)->verifyKey($user->two_factor_secret, preg_replace('/\s+/', '', $data['code']));
        } elseif (! empty($data['recovery_code'])) {
            $recoveryCode = trim($data['recovery_code']);

            if (in_array($recoveryCode, $user->two_factor_recovery_codes ?? [], true)) {
                $valid = true;
                $user->consumeRecoveryCode($recoveryCode);
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'The provided two-factor code was invalid.',
            ]);
        }

        $remember = $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget('login.2fa.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->claimGuestQuoteRequests($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
