<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ASSIGNABLE_ROLES = ['ceo', 'admin', 'specialist', 'expert', 'employee', 'intern', 'client'];

    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(20);
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
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

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, "You can't deactivate your own account.");

        $user->update([
            'is_active' => ! $user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
        ]);

        return back()->with('status', $user->is_active ? "{$user->name} deactivated." : "{$user->name} reactivated.");
    }
}
