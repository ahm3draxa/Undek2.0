<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Support\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles, permissions and teams (departments with their tools), from
 * App\Support\Organization. Safe to re-run on a fresh install; on production
 * use the Roles & Teams screen instead — re-running this resets every role's
 * and team's permissions to the defaults.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(Organization::PERMISSIONS) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(Organization::OWNER, 'web')->syncPermissions(array_keys(Organization::PERMISSIONS));

        foreach (Organization::ROLE_PERMISSIONS as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        foreach (Organization::DEPARTMENTS as $slug => $department) {
            Department::firstOrCreate(['slug' => $slug], ['name' => $department['name']])
                ->syncPermissions($department['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // A known login is only ever created on development and test machines.
        // On a real server, create the first owner deliberately (see README).
        if (app()->environment(['local', 'testing']) && ! User::where('email', 'ceo@undek.com')->exists()) {
            User::create([
                'name' => 'Undek CEO',
                'email' => 'ceo@undek.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ])->assignRole(Organization::OWNER);
        }
    }
}
