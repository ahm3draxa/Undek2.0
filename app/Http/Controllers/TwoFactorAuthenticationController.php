<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Generates a fresh secret and starts setup — the user isn't "enrolled"
     * (hasEnabledTwoFactorAuthentication) until they confirm a valid code.
     */
    public function enable(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('profile.edit')
            ->with('status', 'Scan the QR code with your authenticator app, then enter the 6-digit code to confirm.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $valid = $user->two_factor_secret
            && (new Google2FA)->verifyKey($user->two_factor_secret, preg_replace('/\s+/', '', $data['code']));

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'The provided code was invalid.',
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return redirect()->route('profile.edit')
            ->with('status', 'Two-factor authentication is now enabled. Save your recovery codes somewhere safe — they will not be shown again.')
            ->with('recoveryCodes', $recoveryCodes);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 422);

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return redirect()->route('profile.edit')
            ->with('status', 'Recovery codes regenerated. Your old codes no longer work.')
            ->with('recoveryCodes', $recoveryCodes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(10)).'-'.Str::lower(Str::random(10)))
            ->all();
    }
}
