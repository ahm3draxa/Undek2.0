<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="pencil" :title="'Edit — ' . $position->name" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>

                <form method="POST" action="{{ route('positions.update', $position) }}" class="space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <x-input-label for="name" value="Position name" />
                        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $position->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="department_id" value="Department" />
                            <select id="department_id" name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $position->department_id) == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="rank" value="Rank (seniority within its department)" />
                            <x-text-input id="rank" name="rank" type="number" min="0" max="100" class="block mt-1 w-full" :value="old('rank', $position->rank)" required />
                            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 rounded-md bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                        A position doesn't grant access by itself. Whoever holds it gets the tools of its department — set those on
                        <a href="{{ route('roles.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Roles &amp; Teams</a>.
                    </p>

                    <div>
                        <x-input-label value="Skills expected of this position" />
                        <p class="text-xs text-gray-400 mb-2">Ties the org chart to work-matching — someone holding this position but missing these skills will be flagged on their profile.</p>
                        <div class="max-h-64 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                            @php $currentSkills = old('skills', $position->skills->pluck('id')->all()); @endphp
                            @forelse ($skills as $category => $categorySkills)
                                <div>
                                    <div class="text-[10px] font-jetbrains uppercase tracking-widest text-gray-400 mb-2">{{ $category }}</div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                                        @foreach ($categorySkills as $skill)
                                            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}"
                                                    @checked(in_array($skill->id, $currentSkills))
                                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                                {{ $skill->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No skills exist yet — <a href="{{ route('skills.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some to the master list</a> first.</p>
                            @endforelse
                        </div>
                    </div>

                    <x-primary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />Save position</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
