@csrf
@isset($service) @method('PUT') @endisset

<div class="flex items-center gap-4">
    <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,rgba(124,92,255,.14),rgba(34,211,238,.14))">
        <x-icon :name="$service->icon ?? 'sparkles'" class="w-7 h-7" style="color:#7c5cff" />
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        The icon is picked automatically from the service name — no need to choose one.
        @isset($service) Rename the service to change it. @endisset
    </p>
</div>

<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $service->name ?? '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="slug" value="Slug" />
    <x-text-input id="slug" name="slug" type="text" class="block mt-1 w-full" :value="old('slug', $service->slug ?? '')" required />
    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
</div>

<div>
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">{{ old('description', $service->description ?? '') }}</textarea>
</div>

<div>
    <x-input-label for="department_id" value="Delivered by team" />
    <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
        <option value="">Choose a team…</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected((string) old('department_id', $service->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">When suggesting a project lead or team, people in this team are listed first.</p>
    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
</div>

<div>
    <x-input-label value="Required skills" />
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">People with at least one of these skills are suggested first when assigning work for this service — from any team.</p>
    @if ($skills->isEmpty())
        <p class="text-sm text-gray-400">No skills defined yet — <a href="{{ route('skills.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some first</a>.</p>
    @else
        @php $selectedSkillIds = old('skills', ($service->skills ?? collect())->pluck('id')->all()); @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-2">
            @foreach ($skills as $skill)
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="skills[]" value="{{ $skill->id }}" @checked(in_array($skill->id, $selectedSkillIds)) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                    {{ $skill->name }}
                </label>
            @endforeach
        </div>
    @endif
</div>

<label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true)) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
    Active (visible on the public site)
</label>

<x-primary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />{{ isset($service) ? 'Save changes' : 'Create service' }}</x-primary-button>
