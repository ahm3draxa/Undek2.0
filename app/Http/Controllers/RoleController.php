<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Built-in roles the rest of the app relies on by name (hasRole('ceo') etc).
     * The CEO role always keeps every permission and can't be edited or removed here.
     */
    private const PROTECTED_ROLES = ['ceo', 'admin', 'specialist', 'expert', 'employee', 'intern', 'client'];

    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        // The CEO role is always kept in sync with every permission that exists.
        $ceo = $roles->firstWhere('name', 'ceo');
        if ($ceo && $ceo->permissions->count() !== $permissions->count()) {
            $ceo->syncPermissions($permissions);
            $ceo->load('permissions');
        }

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', "Role \"{$role->name}\" created.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'ceo', 422, 'The CEO role always has full access and cannot be edited.');

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', "Permissions updated for \"{$role->name}\".");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::PROTECTED_ROLES, true), 422, 'Built-in roles can\'t be deleted.');
        abort_if($role->users()->exists(), 422, 'Reassign everyone with this role before deleting it.');

        $role->delete();

        return redirect()->route('roles.index')->with('status', 'Role removed.');
    }
}
