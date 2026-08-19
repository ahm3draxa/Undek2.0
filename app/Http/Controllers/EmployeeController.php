<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\EmployeePromotion;
use App\Models\Position;
use App\Models\ProjectFeedback;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    private const ONBOARD_ROLES = ['ceo', 'admin', 'specialist', 'expert', 'employee', 'intern'];

    public function index(): View
    {
        $employees = User::with(['employeeProfile.manager', 'employeeProfile.department', 'employeeProfile.position', 'roles'])
            ->whereHas('employeeProfile')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $employees->count(),
            'interns' => $employees->filter(fn ($e) => $e->employeeProfile->employment_type === 'intern')->count(),
            'departments' => $employees->pluck('employeeProfile.department_id')->filter()->unique()->count(),
            'unassigned' => $employees->filter(fn ($e) => ! $e->employeeProfile->position_id)->count(),
        ];

        return view('employees.index', compact('employees', 'stats'));
    }

    public function create(): View
    {
        $managers = User::whereHas('employeeProfile')->with(['employeeProfile.position', 'roles'])->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('employees.create', [
            'managers' => $managers,
            'roles' => self::ONBOARD_ROLES,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Onboard a brand-new employee: creates their user account, assigns a role
     * and a starting profile, all in one step.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', self::ONBOARD_ROLES)],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employment_type' => ['required', 'in:full_time,part_time,contractor,intern'],
            'hire_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'compensation_type' => ['required', 'in:'.implode(',', array_keys(EmployeeProfile::COMPENSATION_TYPES))],
            'base_salary' => ['nullable', 'required_if:compensation_type,fixed_salary', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'required_if:compensation_type,hourly', 'numeric', 'min:0'],
            'fixed_project_price' => ['nullable', 'required_if:compensation_type,fixed_project_price', 'numeric', 'min:0'],
            'project_percentage' => ['nullable', 'required_if:compensation_type,project_percentage', 'numeric', 'min:0', 'max:100'],
            'bio' => ['nullable', 'string'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $temporaryPassword = Str::password(14);

        $employee = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $employee->assignRole($data['role']);

        EmployeeProfile::create([
            'user_id' => $employee->id,
            'manager_id' => $data['manager_id'] ?? null,
            'position_id' => $data['position_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'employment_type' => $data['employment_type'],
            'hire_date' => $data['hire_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'base_salary' => $data['base_salary'] ?? null,
            'compensation_type' => $data['compensation_type'],
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'fixed_project_price' => $data['fixed_project_price'] ?? null,
            'project_percentage' => $data['project_percentage'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_public' => $request->boolean('is_public'),
        ]);

        $employee->syncPermissionsFromPositions();

        event(new Registered($employee));

        return redirect()->route('employees.show', $employee)->with([
            'status' => "Employee added. Temporary password: {$temporaryPassword} — share this securely, it won't be shown again.",
        ]);
    }

    public function show(User $employee): View
    {
        $employee->load([
            'employeeProfile.manager', 'employeeProfile.department', 'employeeProfile.position.department', 'employeeProfile.position.skills',
            'roles', 'promotions.promotedBy', 'promotions.oldPosition.department', 'promotions.newPosition.department',
            'contributions.project', 'payrollAdjustments', 'skills.category',
            'secondaryPositions.department', 'secondaryPositions.skills',
        ]);
        $availableSkills = Skill::with('category')->orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        $currentPosition = $employee->employeeProfile?->position;
        $ladderPositions = $currentPosition
            ? Position::where('department_id', $currentPosition->department_id)->where('id', '!=', $currentPosition->id)->orderByDesc('rank')->get()
            : collect();

        $heldPositionIds = $employee->allPositions()->pluck('id');
        $addablePositions = $positions->whereNotIn('id', $heldPositionIds)->values();

        // Skill gap: what every held position expects vs. what's actually on the employee's profile.
        $employeeSkillIds = $employee->skills->pluck('id');
        $expectedSkills = $employee->allPositions()->flatMap->skills->unique('id');
        $missingSkills = $expectedSkills->reject(fn ($skill) => $employeeSkillIds->contains($skill->id))->values();

        $clientRating = ProjectFeedback::where('audience', ProjectFeedback::CLIENT_TO_UNDEK)
            ->whereHas('project', fn ($q) => $q->where('lead_expert_id', $employee->id))
            ->avg('rating');
        $clientRatingCount = ProjectFeedback::where('audience', ProjectFeedback::CLIENT_TO_UNDEK)
            ->whereHas('project', fn ($q) => $q->where('lead_expert_id', $employee->id))
            ->count();

        return view('employees.show', compact(
            'employee', 'availableSkills', 'positions', 'departments', 'ladderPositions', 'addablePositions', 'missingSkills',
            'clientRating', 'clientRatingCount'
        ));
    }

    public function edit(User $employee): View
    {
        $employee->load('employeeProfile.position', 'employeeProfile.department');
        $managers = User::whereHas('employeeProfile')->with(['employeeProfile.position', 'roles'])->where('id', '!=', $employee->id)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'managers'));
    }

    /**
     * Update an employee's profile (position, department, salary, manager, etc).
     */
    public function update(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:'.implode(',', self::ONBOARD_ROLES)],
            'employment_type' => ['required', 'in:full_time,part_time,contractor,intern'],
            'hire_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'compensation_type' => ['required', 'in:'.implode(',', array_keys(EmployeeProfile::COMPENSATION_TYPES))],
            'base_salary' => ['nullable', 'required_if:compensation_type,fixed_salary', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'required_if:compensation_type,hourly', 'numeric', 'min:0'],
            'fixed_project_price' => ['nullable', 'required_if:compensation_type,fixed_project_price', 'numeric', 'min:0'],
            'project_percentage' => ['nullable', 'required_if:compensation_type,project_percentage', 'numeric', 'min:0', 'max:100'],
            'equity_shares' => ['nullable', 'numeric', 'min:0'],
            'bio' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        abort_if((int) ($data['manager_id'] ?? 0) === $employee->id, 422, 'An employee cannot manage themselves.');

        $role = $data['role'] ?? null;
        unset($data['role']);

        if ($role && $request->user()->can('manage users') && ! $employee->hasRole($role)) {
            if ($employee->id === $request->user()->id && $role !== 'ceo' && $employee->hasRole('ceo')) {
                abort_if(User::role('ceo')->count() <= 1, 422, 'At least one CEO account must remain.');
            }

            $employee->syncRoles([$role]);
        }

        $data['is_public'] = $request->boolean('is_public');

        EmployeeProfile::updateOrCreate(['user_id' => $employee->id], $data);

        return redirect()->route('employees.show', $employee)->with('status', 'Profile updated.');
    }

    /**
     * Promote or demote within the employee's current department — the new position
     * must belong to the same department, and direction is derived from comparing ranks.
     */
    public function promote(Request $request, User $employee): RedirectResponse
    {
        $profile = $employee->employeeProfile;
        abort_unless($profile?->position, 422, 'This employee has no starting position yet — use Transfer to assign one.');

        $data = $request->validate([
            'new_position_id' => ['required', 'exists:positions,id'],
            'new_salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $newPosition = Position::findOrFail($data['new_position_id']);
        abort_unless($newPosition->department_id === $profile->position->department_id, 422,
            'A promotion or demotion has to stay within the same department — use Transfer to move departments.');

        $direction = match (true) {
            $newPosition->rank > $profile->position->rank => 'promotion',
            $newPosition->rank < $profile->position->rank => 'demotion',
            default => 'lateral',
        };

        EmployeePromotion::create([
            'user_id' => $employee->id,
            'old_position_id' => $profile->position_id,
            'new_position_id' => $newPosition->id,
            'direction' => $direction,
            'old_salary' => $profile->base_salary,
            'new_salary' => $data['new_salary'] ?? $profile->base_salary,
            'notes' => $data['notes'] ?? null,
            'promoted_by' => $request->user()->id,
            'effective_date' => $data['effective_date'],
        ]);

        $profile->update([
            'position_id' => $newPosition->id,
            'base_salary' => $data['new_salary'] ?? $profile->base_salary,
        ]);

        $employee->syncPermissionsFromPositions();

        return redirect()->route('employees.show', $employee)->with('status', ucfirst($direction).' recorded.');
    }

    /**
     * Move an employee to a different department, assigning a position within it manually
     * (ranks aren't comparable across departments, so this doesn't imply promotion/demotion).
     */
    public function transfer(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'new_position_id' => ['required', 'exists:positions,id'],
            'new_salary' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $newPosition = Position::findOrFail($data['new_position_id']);
        abort_unless((string) $newPosition->department_id === (string) $data['department_id'], 422, 'Selected position does not belong to the chosen department.');

        $profile = $employee->employeeProfile;
        abort_unless($profile, 422, 'This employee has no profile yet.');

        EmployeePromotion::create([
            'user_id' => $employee->id,
            'old_position_id' => $profile->position_id,
            'new_position_id' => $newPosition->id,
            'direction' => 'transfer',
            'old_salary' => $profile->base_salary,
            'new_salary' => $data['new_salary'] ?? $profile->base_salary,
            'notes' => $data['notes'] ?? null,
            'promoted_by' => $request->user()->id,
            'effective_date' => $data['effective_date'],
        ]);

        $profile->update([
            'department_id' => $data['department_id'],
            'position_id' => $newPosition->id,
            'base_salary' => $data['new_salary'] ?? $profile->base_salary,
        ]);

        $employee->syncPermissionsFromPositions();

        return redirect()->route('employees.show', $employee)->with('status', 'Transferred.');
    }

    /**
     * Add a secondary position — held alongside the primary one, e.g. covering another
     * function part-time. Contributes its own permissions to the employee's effective set.
     */
    public function addSecondaryPosition(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'position_id' => ['required', 'exists:positions,id'],
        ]);

        abort_if($employee->employeeProfile?->position_id === (int) $data['position_id'], 422, 'That is already their primary position.');

        $employee->secondaryPositions()->syncWithoutDetaching([$data['position_id'] => ['assigned_at' => now()]]);
        $employee->syncPermissionsFromPositions();

        return redirect()->route('employees.show', $employee)->with('status', 'Secondary position added.');
    }

    public function removeSecondaryPosition(User $employee, Position $position): RedirectResponse
    {
        $employee->secondaryPositions()->detach($position->id);
        $employee->syncPermissionsFromPositions();

        return redirect()->route('employees.show', $employee)->with('status', 'Secondary position removed.');
    }

    /**
     * Log a contribution / achievement for an employee, optionally tied to a project.
     */
    public function logContribution(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contribution_date' => ['required', 'date'],
        ]);

        $employee->contributions()->create($data + ['logged_by' => $request->user()->id]);

        return redirect()->route('employees.show', $employee)->with('status', 'Contribution logged.');
    }

    /**
     * Add or update a skill + proficiency level for this employee.
     */
    public function addSkill(Request $request, User $employee): RedirectResponse
    {
        $data = $request->validate([
            'skill_id' => ['required', 'exists:skills,id'],
            'level' => ['required', 'in:beginner,intermediate,expert'],
        ]);

        $employee->skills()->syncWithoutDetaching([
            $data['skill_id'] => ['level' => $data['level']],
        ]);

        return redirect()->route('employees.show', $employee)->with('status', 'Skill added.');
    }

    public function removeSkill(User $employee, Skill $skill): RedirectResponse
    {
        $employee->skills()->detach($skill->id);

        return redirect()->route('employees.show', $employee)->with('status', 'Skill removed.');
    }

}
