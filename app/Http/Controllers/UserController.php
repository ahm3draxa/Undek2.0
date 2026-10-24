<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Account administration. Every action goes through ensureCanManage(): owner
 * and admin accounts can only be changed by an owner (see
 * User::canManageAccountOf), so 'manage users' can't be used to escalate.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('roles', 'employeeProfile.position')->orderBy('name')->paginate(20);
        $assignableRoles = $request->user()->assignableRoles();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'deactivated' => User::where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'assignableRoles', 'stats'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'role' => ['required', Rule::in($actor->assignableRoles())],
        ]);

        // Owners may change their own role, as long as an owner remains.
        if ($user->id !== $actor->id) {
            $this->ensureCanManage($actor, $user);
        } else {
            abort_unless($actor->isOwner(), 403);
        }

        if ($user->isOwner() && $data['role'] !== Organization::OWNER) {
            abort_if(User::role(Organization::OWNER)->count() <= 1, 422, 'At least one owner account must remain.');
        }

        $user->syncRoles([$data['role']]);

        Log::warning('User role changed', ['actor_id' => $actor->id, 'target_id' => $user->id, 'role' => $data['role']]);

        return back()->with('status', "{$user->name} is now ".Organization::roleLabel($data['role']).'.');
    }

    /**
     * Issue a new temporary password for a user — shown once, same pattern as onboarding.
     * Doesn't depend on outbound mail being configured, unlike a reset-link email.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $user);

        $temporaryPassword = Str::password(14);

        $user->update(['password' => Hash::make($temporaryPassword)]);
        $this->endSessions($user);

        Log::warning('Admin reset a user\'s password', ['actor_id' => $request->user()->id, 'target_id' => $user->id]);

        return back()->with('status', "Password reset for {$user->name}. Temporary password: {$temporaryPassword} — share this securely, it won't be shown again.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, "You can't deactivate your own account.");
        $this->ensureCanManage($request->user(), $user);

        $user->update([
            'is_active' => ! $user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
        ]);

        // Deactivation takes effect immediately, not at their next login.
        if (! $user->is_active) {
            $this->endSessions($user);
        }

        return back()->with('status', $user->is_active ? "{$user->name} reactivated." : "{$user->name} deactivated and signed out.");
    }

    /**
     * Admin-side email correction for a locked-out user who can't reach their
     * own profile page to self-service it. Re-verification is required, same
     * as when a user changes their own email.
     */
    public function updateEmail(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManage($request->user(), $user);

        $data = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $oldEmail = $user->email;

        $user->forceFill([
            'email' => $data['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

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
        $this->ensureCanManage($request->user(), $user);
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

    private function ensureCanManage(User $actor, User $target): void
    {
        abort_unless($actor->canManageAccountOf($target), 403, 'Only an owner can change an owner or admin account.');
    }

    /**
     * Signs the person out everywhere: database sessions, and any "remember
     * me" cookie (by rotating the token it's checked against).
     */
    private function endSessions(User $user): void
    {
        // No session matches a revoked marker, so EnforceSingleSession signs
        // out whatever session they have on its next request.
        $user->forceFill([
            'current_session_id' => 'revoked:'.Str::random(32),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }
}
