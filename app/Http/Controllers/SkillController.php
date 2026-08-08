<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $skills = Skill::withCount('users')->with('category')->orderBy('name')->get()->sortBy(fn ($skill) => $skill->category->name ?? '');
        $categories = SkillCategory::orderBy('name')->get();

        return view('skills.index', compact('skills', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skills,name'],
            'category_id' => ['nullable', 'exists:skill_categories,id'],
        ]);

        Skill::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'category_id' => $data['category_id'] ?? null,
        ]);

        return redirect()->route('skills.index')->with('status', 'Skill added.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();

        return redirect()->route('skills.index')->with('status', 'Skill removed.');
    }
}
