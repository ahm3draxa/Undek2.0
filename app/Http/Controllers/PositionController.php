<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PositionController extends Controller
{
    public function index(): View
    {
        $withHolders = fn ($q) => $q->withCount('employeeProfiles')->with(['skills', 'employeeProfiles.user', 'users']);

        $departments = Department::with(['positions' => fn ($q) => $withHolders($q)->orderByDesc('rank')])->orderBy('name')->get();
        $unassigned = $withHolders(Position::whereNull('department_id'))->orderByDesc('rank')->get();

        return view('positions.index', compact('departments', 'unassigned'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $skills = Skill::with('category')->orderBy('name')->get()->groupBy(fn ($skill) => $skill->category->name ?? 'Other');

        return view('positions.create', compact('departments', 'permissions', 'skills'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'rank' => ['required', 'integer', 'min:0', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'skills' => ['array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $position = Position::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'department_id' => $data['department_id'] ?? null,
            'rank' => $data['rank'],
        ]);

        $position->syncPermissions($data['permissions'] ?? []);
        $position->skills()->sync($data['skills'] ?? []);

        return redirect()->route('positions.index')->with('status', 'Position created.');
    }

    public function edit(Position $position): View
    {
        $departments = Department::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $skills = Skill::with('category')->orderBy('name')->get()->groupBy(fn ($skill) => $skill->category->name ?? 'Other');
        $position->load('permissions', 'skills');

        return view('positions.edit', compact('position', 'departments', 'permissions', 'skills'));
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name,'.$position->id],
            'department_id' => ['nullable', 'exists:departments,id'],
            'rank' => ['required', 'integer', 'min:0', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'skills' => ['array'],
            'skills.*' => ['integer', 'exists:skills,id'],
        ]);

        $position->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'department_id' => $data['department_id'] ?? null,
            'rank' => $data['rank'],
        ]);

        $position->syncPermissions($data['permissions'] ?? []);
        $position->skills()->sync($data['skills'] ?? []);

        // Permission set changed — everyone currently holding this position needs their effective permissions recomputed.
        foreach ($position->employeeProfiles()->with('user')->get() as $profile) {
            $profile->user?->syncPermissionsFromPositions();
        }
        foreach ($position->users as $user) {
            $user->syncPermissionsFromPositions();
        }

        return redirect()->route('positions.index')->with('status', 'Position updated.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        abort_if($position->employeeProfiles()->exists() || $position->users()->exists(), 422, 'This position is still held by someone — reassign them first.');

        $position->delete();

        return redirect()->route('positions.index')->with('status', 'Position removed.');
    }
}
