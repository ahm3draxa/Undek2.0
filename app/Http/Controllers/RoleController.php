<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & Teams: what each role allows, and which tools each team gets.
 * Someone's access is their role's permissions plus their teams' tools.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->get()
            ->sortBy(fn (Role $role) => array_search($role->name, array_keys(Organization::ROLES), true) ?? 99)
            ->values();

        $departments = Department::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get()
            ->sortBy(fn (Permission $permission) => array_search($permission->name, array_keys(Organization::PERMISSIONS), true) ?? 99)
            ->values();

        // The owner role is always kept in sync with every permission that exists.
        $owner = $roles->firstWhere('name', Organization::OWNER);
        if ($owner && $owner->permissions->count() !== $permissions->count()) {
            $owner->syncPermissions($permissions);
            $owner->load('permissions');
        }

        return view('roles.index', compact('roles', 'departments', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === Organization::OWNER, 422, 'The owner role always has full access and cannot be edited.');

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', 'Permissions updated for '.Organization::roleLabel($role->name).'.');
    }

    /**
     * A team's tools. Everyone in the team is resynced straight away.
     */
    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_keys(Organization::PERMISSIONS))],
        ]);

        // Team tools are for the team's work — never account or role administration.
        $data['permissions'] = array_values(array_diff($data['permissions'] ?? [], ['manage users', 'manage roles']));

        $department->syncPermissions($data['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $department->members()->get()->each->syncTeamPermissions();

        return redirect()->route('roles.index')->with('status', "Team tools updated for {$department->name}.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(array_key_exists($role->name, Organization::ROLES), 422, 'Built-in roles can\'t be deleted.');
        abort_if($role->users()->exists(), 422, 'Reassign everyone with this role before deleting it.');

        $role->delete();

        return redirect()->route('roles.index')->with('status', 'Role removed.');
    }
}
