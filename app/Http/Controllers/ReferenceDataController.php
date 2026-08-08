<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReferenceDataController extends Controller
{
    private const TYPES = [
        'department' => Department::class,
        'position' => Position::class,
        'skill-category' => SkillCategory::class,
    ];

    public function index(): View
    {
        $departments = Department::withCount('employeeProfiles')->orderBy('name')->get();
        $positions = Position::withCount('employeeProfiles')->orderBy('name')->get();
        $skillCategories = SkillCategory::withCount('skills')->orderBy('name')->get();

        return view('reference-data.index', compact('departments', 'positions', 'skillCategories'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $model = self::TYPES[$type] ?? abort(404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:'.(new $model)->getTable().',name'],
        ]);

        $model::create(['name' => $data['name'], 'slug' => Str::slug($data['name'])]);

        return redirect()->route('reference-data.index')->with('status', 'Added.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = self::TYPES[$type] ?? abort(404);
        $record = $model::findOrFail($id);

        $inUse = match ($model) {
            Department::class, Position::class => $record->employeeProfiles()->exists(),
            SkillCategory::class => $record->skills()->exists(),
            default => false,
        };

        abort_if($inUse, 422, 'This is still in use — reassign whoever/whatever uses it first.');

        $record->delete();

        return redirect()->route('reference-data.index')->with('status', 'Removed.');
    }
}
