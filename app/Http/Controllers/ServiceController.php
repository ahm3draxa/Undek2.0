<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::withCount('quoteRequests')->with('skills')->latest()->get();

        return view('services.index', compact('services'));
    }

    public function create(): View
    {
        $skills = Skill::orderBy('category')->orderBy('name')->get();

        return view('services.create', compact('skills'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:services,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'skills' => ['array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        $data['icon'] = Service::suggestIcon($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = $request->user()->id;

        $service = Service::create(collect($data)->except('skills')->all());
        $service->skills()->sync($data['skills'] ?? []);

        return redirect()->route('services.index')->with('status', 'Service created.');
    }

    public function edit(Service $service): View
    {
        $skills = Skill::orderBy('category')->orderBy('name')->get();
        $service->load('skills');

        return view('services.edit', compact('service', 'skills'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:services,slug,'.$service->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'skills' => ['array'],
            'skills.*' => ['exists:skills,id'],
        ]);

        // Re-derive the icon whenever the name changes so it stays in sync automatically.
        if ($data['name'] !== $service->name) {
            $data['icon'] = Service::suggestIcon($data['name']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $service->update(collect($data)->except('skills')->all());
        $service->skills()->sync($data['skills'] ?? []);

        return redirect()->route('services.index')->with('status', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('services.index')->with('status', 'Service removed.');
    }
}
