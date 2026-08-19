<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="sparkles" title="Skills" subtitle="The master skill list used to route work to the right people." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-stat-card icon="sparkles" label="Total skills" :value="$skills->count()" accent="violet" />
                <x-stat-card icon="building-office" label="Categories" :value="$categories->count()" accent="pink" />
                <x-stat-card icon="users" label="Skills in use" :value="$skills->where('users_count', '>', 0)->count()" accent="green" />
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                This is the master skill list. Attach skills (with a level) to people from their
                <a href="{{ route('employees.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">employee profile</a>,
                and mark which skills a <a href="{{ route('services.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">service</a> requires —
                only matching people will show up when assigning a specialist, lead expert, or team member.
            </p>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">People</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($skills as $skill)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="px-6 py-4 font-medium">{{ $skill->name }}</td>
                                <td class="px-6 py-4">{{ $skill->category->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $skill->users_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('skills.destroy', $skill) }}" onsubmit="return confirm('Remove this skill?')">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 text-red-500 hover:text-red-600 hover:underline"><x-icon name="trash" class="w-4 h-4" />Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">No skills yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-panel icon="plus" title="Add a skill" accent="amber">
                <form method="POST" action="{{ route('skills.store') }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Skill name" />
                        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" placeholder="e.g. Laravel" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="category_id" value="Category (optional)" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            <option value="">No category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @if ($categories->isEmpty())
                            <p class="text-xs text-gray-400 mt-1">No categories yet — <a href="{{ route('reference-data.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some</a>.</p>
                        @endif
                    </div>
                    <div class="col-span-2">
                        <x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add skill</x-primary-button>
                    </div>
                </form>
            </x-panel>
        </div>
    </div>
</x-app-layout>
