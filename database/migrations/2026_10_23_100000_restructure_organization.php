<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Collapses the overlapping org vocabulary into one concept per question
 * (see App\Support\Organization):
 *
 *  - roles ceo/admin/specialist/expert/employee/intern/client → owner/admin/staff/client;
 *    what specialists and experts could do now comes from their team (department)
 *  - ten departments → Management, Web Development, SEO & Marketing, Design & Video,
 *    IT Support, Sales; each team carries its own tool permissions
 *  - positions stop granting permissions; services belong to a department
 *  - skill level "expert" → "advanced"; project roles become a fixed list
 *
 * Existing people keep what they could do before: former specialists who
 * aren't in Sales are given a Sales secondary position so they can still
 * quote. The definitions below are frozen copies on purpose — this migration
 * must keep doing the same thing even if Organization changes later.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'manage all work', 'create projects', 'assign specialist', 'finalize project', 'send quotation',
        'lead projects', 'assign project team', 'manage milestones', 'send marketing', 'view info inbox',
        'view support inbox', 'manage payroll', 'manage employees', 'manage services', 'manage skills',
        'manage users', 'manage roles',
    ];

    private const OBSOLETE_PERMISSIONS = ['view quote requests', 'deliver project', 'view own projects', 'view inbox'];

    private const ROLE_PERMISSIONS = [
        'admin' => ['manage all work', 'create projects', 'assign specialist', 'finalize project', 'assign project team',
            'manage milestones', 'manage employees', 'manage services', 'manage skills', 'manage users'],
        'staff' => ['manage milestones'],
        'client' => [],
    ];

    private const DEPARTMENTS = [
        'management' => ['Management', ['view info inbox', 'manage payroll']],
        'web-development' => ['Web Development', ['lead projects', 'assign project team']],
        'seo-marketing' => ['SEO & Marketing', ['lead projects', 'assign project team', 'send marketing']],
        'design-video' => ['Design & Video', ['lead projects', 'assign project team']],
        'it-support' => ['IT Support', ['lead projects', 'assign project team', 'view support inbox']],
        'sales' => ['Sales', ['send quotation']],
    ];

    private const DEPARTMENT_MERGES = [
        'executive' => 'management', 'operations' => 'management', 'finance' => 'management',
        'human-resources' => 'management', 'engineering' => 'web-development', 'design' => 'design-video',
        'marketing' => 'seo-marketing', 'customer-support' => 'it-support', 'it-technical-support' => 'it-support',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('icon')->constrained('departments')->nullOnDelete();
        });

        $departments = $this->restructureDepartments();
        $this->restructurePositions($departments);
        $this->mapServicesToDepartments($departments);

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $this->restructureRoles($departments);

        foreach (self::DEPARTMENTS as $slug => [, $permissions]) {
            $departments[$slug]->syncPermissions($permissions);
        }

        Permission::whereIn('name', self::OBSOLETE_PERMISSIONS)->where('guard_name', 'web')->delete();

        $this->renameSkillLevel();

        DB::table('project_team_members')->where('role_on_project', 'Specialist')->update(['role_on_project' => 'Sales contact']);
        DB::table('project_team_members')->whereNotNull('role_on_project')
            ->whereNotIn('role_on_project', ['Member', 'Reviewer', 'Sales contact'])
            ->update(['role_on_project' => 'Member']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Direct permissions are now exactly the team tools of every
        // department each person holds a position in.
        User::query()->each(fn (User $user) => $user->syncTeamPermissions());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('This restructure merges departments and roles and cannot be reversed automatically. Restore the database backup taken before deploying.');
    }

    /**
     * @return array<string, Department>
     */
    private function restructureDepartments(): array
    {
        $targets = [];
        foreach (self::DEPARTMENTS as $slug => [$name]) {
            $targets[$slug] = Department::where('slug', $slug)->first()
                ?? Department::where('name', $name)->first()
                ?? Department::create(['slug' => $slug, 'name' => $name]);
            $targets[$slug]->update(['slug' => $slug, 'name' => $name]);
        }

        foreach (self::DEPARTMENT_MERGES as $oldSlug => $targetSlug) {
            $old = Department::where('slug', $oldSlug)->first();

            if (! $old || $old->id === $targets[$targetSlug]->id) {
                continue;
            }

            DB::table('positions')->where('department_id', $old->id)->update(['department_id' => $targets[$targetSlug]->id]);
            DB::table('employee_profiles')->where('department_id', $old->id)->update(['department_id' => $targets[$targetSlug]->id]);
            DB::table('model_has_permissions')->where('model_type', Department::class)->where('model_id', $old->id)->delete();
            $old->delete();
        }

        return $targets;
    }

    private function restructurePositions(array $departments): void
    {
        $management = $departments['management']->id;

        // Founder titles live in Management, above everyone else there.
        $ceo = DB::table('positions')->whereIn('slug', ['ceo-founder', 'chief-executive-officer'])->orderByDesc('slug')->first();
        $ceoData = ['name' => 'CEO & Founder', 'slug' => 'ceo-founder', 'department_id' => $management, 'rank' => 100, 'updated_at' => now()];
        $ceo
            ? DB::table('positions')->where('id', $ceo->id)->update($ceoData)
            : DB::table('positions')->insert($ceoData + ['created_at' => now()]);

        if (! DB::table('positions')->where('slug', 'co-founder')->exists()) {
            DB::table('positions')->insert([
                'name' => 'Co-Founder', 'slug' => 'co-founder', 'department_id' => $management, 'rank' => 90,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Video belongs with design, not marketing.
        DB::table('positions')->where('slug', 'video-editor')->update(['department_id' => $departments['design-video']->id]);

        // Positions no longer grant permissions — teams do.
        DB::table('model_has_permissions')->where('model_type', 'App\\Models\\Position')->delete();

        // A person's department is their primary position's department.
        DB::table('employee_profiles')->whereNotNull('position_id')->orderBy('id')->each(function ($profile) {
            $departmentId = DB::table('positions')->where('id', $profile->position_id)->value('department_id');
            DB::table('employee_profiles')->where('id', $profile->id)->update(['department_id' => $departmentId]);
        });
    }

    private function mapServicesToDepartments(array $departments): void
    {
        $rules = [
            'design-video' => ['design', 'graphic', 'video', 'brand', 'logo', 'animation', 'motion'],
            'seo-marketing' => ['seo', 'marketing', 'social', 'ads', 'content', 'advertis'],
            'it-support' => ['support', 'network', 'technical', 'it '],
            'web-development' => ['web', 'commerce', 'wordpress', 'app', 'software', 'development'],
        ];

        DB::table('services')->orderBy('id')->each(function ($service) use ($rules, $departments) {
            $haystack = Str::lower($service->name.' '.$service->slug).' ';

            foreach ($rules as $slug => $keywords) {
                if (Str::contains($haystack, $keywords)) {
                    DB::table('services')->where('id', $service->id)->update(['department_id' => $departments[$slug]->id]);

                    return;
                }
            }
        });
    }

    private function restructureRoles(array $departments): void
    {
        $roles = collect(['owner', 'admin', 'staff', 'client'])->mapWithKeys(fn ($name) => [$name => Role::findOrCreate($name, 'web')]);
        $legacy = Role::whereIn('name', ['ceo', 'specialist', 'expert', 'employee', 'intern'])->where('guard_name', 'web')->get()->keyBy('name');

        $usersWithLegacy = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('role_id', $legacy->pluck('id')->concat([$roles['admin']->id, $roles['client']->id]))
            ->get()
            ->groupBy('model_id');

        $salesPosition = null;

        foreach ($usersWithLegacy as $userId => $rows) {
            $names = $rows->map(fn ($row) => $legacy->firstWhere('id', $row->role_id)?->name
                ?? $roles->first(fn ($role) => $role->id === $row->role_id)?->name)->filter();

            $newRole = match (true) {
                $names->contains('ceo') => 'owner',
                $names->contains('admin') => 'admin',
                $names->intersect(['specialist', 'expert', 'employee', 'intern'])->isNotEmpty() => 'staff',
                default => 'client',
            };

            DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $userId)
                ->whereIn('role_id', $legacy->pluck('id')->concat($roles->pluck('id')))->delete();
            DB::table('model_has_roles')->insert(['role_id' => $roles[$newRole]->id, 'model_type' => User::class, 'model_id' => $userId]);

            // Quoting used to come with the specialist role; it now comes with
            // the Sales team, so former specialists join Sales to keep it.
            if ($newRole === 'staff' && $names->contains('specialist') && ! $this->isInDepartment($userId, $departments['sales']->id)) {
                $salesPosition ??= $this->salesPositionId($departments['sales']->id);

                DB::table('employee_positions')->insertOrIgnore([
                    'user_id' => $userId, 'position_id' => $salesPosition, 'assigned_at' => now()->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                Log::info("Organization restructure: user $userId (former specialist) given a Sales secondary position to keep quoting.");
            }
        }

        $legacy->each->delete();

        $roles['owner']->syncPermissions(self::PERMISSIONS);
        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            $roles[$role]->syncPermissions($permissions);
        }
    }

    private function isInDepartment(int $userId, int $departmentId): bool
    {
        $primary = DB::table('employee_profiles')->join('positions', 'positions.id', '=', 'employee_profiles.position_id')
            ->where('employee_profiles.user_id', $userId)->where('positions.department_id', $departmentId)->exists();

        return $primary || DB::table('employee_positions')->join('positions', 'positions.id', '=', 'employee_positions.position_id')
            ->where('employee_positions.user_id', $userId)->where('positions.department_id', $departmentId)->exists();
    }

    private function salesPositionId(int $salesDepartmentId): int
    {
        $existing = DB::table('positions')->where('slug', 'account-manager')->first();

        if ($existing) {
            DB::table('positions')->where('id', $existing->id)->update(['department_id' => $salesDepartmentId]);

            return $existing->id;
        }

        return DB::table('positions')->insertGetId([
            'name' => 'Account Manager', 'slug' => 'account-manager', 'department_id' => $salesDepartmentId, 'rank' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function renameSkillLevel(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE employee_skills MODIFY level ENUM('beginner','intermediate','expert','advanced') NOT NULL DEFAULT 'intermediate'");
            DB::table('employee_skills')->where('level', 'expert')->update(['level' => 'advanced']);
            DB::statement("ALTER TABLE employee_skills MODIFY level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate'");

            return;
        }

        Schema::table('employee_skills', function (Blueprint $table) {
            $table->enum('level', ['beginner', 'intermediate', 'expert', 'advanced'])->default('intermediate')->change();
        });
        DB::table('employee_skills')->where('level', 'expert')->update(['level' => 'advanced']);
        Schema::table('employee_skills', function (Blueprint $table) {
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('intermediate')->change();
        });
    }
};
