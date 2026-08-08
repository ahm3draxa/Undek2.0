<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage services',
            'manage roles',
            'manage users',
            'manage skills',
            'view quote requests',
            'assign specialist',
            'send quotation',
            'finalize project',
            'assign project team',
            'manage milestones',
            'deliver project',
            'manage payroll',
            'manage employees',
            'view own projects',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ceo = Role::findOrCreate('ceo', 'web');
        $ceo->syncPermissions($permissions);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions([
            'manage services',
            'manage users',
            'manage skills',
            'view quote requests',
            'assign specialist',
            'finalize project',
            'assign project team',
            'manage milestones',
            'manage employees',
        ]);

        $specialist = Role::findOrCreate('specialist', 'web');
        $specialist->syncPermissions([
            'view quote requests',
            'send quotation',
        ]);

        $expert = Role::findOrCreate('expert', 'web');
        $expert->syncPermissions([
            'assign project team',
            'manage milestones',
            'deliver project',
            'view own projects',
        ]);

        $employee = Role::findOrCreate('employee', 'web');
        $employee->syncPermissions([
            'view own projects',
            'manage milestones',
        ]);

        $intern = Role::findOrCreate('intern', 'web');
        $intern->syncPermissions([
            'view own projects',
        ]);

        Role::findOrCreate('client', 'web');

        if (! User::where('email', 'ceo@undek.com')->exists()) {
            $ceoUser = User::create([
                'name' => 'Undek CEO',
                'email' => 'ceo@undek.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $ceoUser->assignRole('ceo');
        }
    }
}
