<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="building-office" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Reference Data') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400">
                These are the shared dropdown lists used across employee profiles and the
                <a href="{{ route('skills.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">skill catalog</a> —
                add to them here so everyone picks from the same options instead of typing free text.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ([
                    ['type' => 'department', 'title' => 'Departments', 'icon' => 'building-office', 'items' => $departments, 'countKey' => 'employee_profiles_count'],
                    ['type' => 'position', 'title' => 'Positions', 'icon' => 'briefcase', 'items' => $positions, 'countKey' => 'employee_profiles_count'],
                    ['type' => 'skill-category', 'title' => 'Skill Categories', 'icon' => 'sparkles', 'items' => $skillCategories, 'countKey' => 'skills_count'],
                ] as $group)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 space-y-3 transition-colors duration-200">
                        <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-icon :name="$group['icon']" class="w-4 h-4" style="color:#7c5cff" />
                            {{ $group['title'] }}
                        </h3>

                        <ul class="space-y-1.5">
                            @forelse ($group['items'] as $item)
                                <li class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-md px-3 py-1.5">
                                    <span>{{ $item->name }} <span class="text-gray-400 text-xs">({{ $item->{$group['countKey']} }})</span></span>
                                    @if ($item->{$group['countKey']} === 0)
                                        <form method="POST" action="{{ route('reference-data.destroy', [$group['type'], $item->id]) }}" onsubmit="return confirm('Remove this?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-500" aria-label="Remove">&times;</button>
                                        </form>
                                    @endif
                                </li>
                            @empty
                                <p class="text-sm text-gray-400">None yet.</p>
                            @endforelse
                        </ul>

                        <form method="POST" action="{{ route('reference-data.store', $group['type']) }}" class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                            @csrf
                            <input type="text" name="name" required placeholder="Add new…" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-2">
                            <x-secondary-button class="mt-2"><x-icon name="plus" class="w-3.5 h-3.5" /></x-secondary-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
