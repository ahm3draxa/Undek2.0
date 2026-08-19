<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ASSIGNABLE_ROLES = ['ceo', 'admin', 'specialist', 'expert', 'employee', 'intern', 'client'];

    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(20);
        $roles = Role::orderBy('name')->get();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'deactivated' => User::where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'roles', 'stats'));
    }

    /**
     * Assign/replace a user's role. Multiple users may hold the 'ceo' role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:'.implode(',', self::ASSIGNABLE_ROLES)],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'ceo' && $user->hasRole('ceo')) {
            $ceoCount = User::role('ceo')->count();
            abort_if($ceoCount <= 1, 422, 'At least one CEO account must remain.');
        }

        $user->syncRoles([$data['role']]);

        return back()->with('status', "{$user->name} is now {$data['role']}.");
    }

    /**
     * Issue a new temporary password for a user — shown once, same pattern as onboarding.
     * Doesn't depend on outbound mail being configured, unlike a reset-link email.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(14);

        $user->update(['password' => Hash::make($temporaryPassword)]);

        return back()->with('status', "Password reset for {$user->name}. Temporary password: {$temporaryPassword} — share this securely, it won't be shown again.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, "You can't deactivate your own account.");

        $user->update([
            'is_active' => ! $user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
        ]);

        return back()->with('status', $user->is_active ? "{$user->name} deactivated." : "{$user->name} reactivated.");
    }

    /**
     * Admin-side email correction for a locked-out user who can't reach their
     * own profile page to self-service it. Re-verification is required, same
     * as when a user changes their own email.
     */
    public function updateEmail(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $oldEmail = $user->email;

        $user->forceFill([
            'email' => $data['email'],
            'email_verified_at' => null,
        ])->save();

        Log::warning('Admin changed a user\'s email', [
            'actor_id' => $request->user()->id,
            'actor_email' => $request->user()->email,
            'target_id' => $user->id,
            'old_email' => $oldEmail,
            'new_email' => $data['email'],
        ]);

        return back()->with('status', "{$user->name}'s email changed to {$data['email']}.");
    }

    /**
     * Recovery path for a user locked out of their own account — no authenticator,
     * no recovery codes. Self-service disable (TwoFactorAuthenticationController)
     * requires the current password, which a locked-out user can't provide either,
     * so this is the only way back in for them.
     */
    public function disableTwoFactor(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasEnabledTwoFactorAuthentication(), 422, "{$user->name} doesn't have two-factor authentication enabled.");

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::warning('Admin disabled two-factor authentication for a user', [
            'actor_id' => $request->user()->id,
            'actor_email' => $request->user()->email,
            'target_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return back()->with('status', "Two-factor authentication disabled for {$user->name}.");
    }
}
