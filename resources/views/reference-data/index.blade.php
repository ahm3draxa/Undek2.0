<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="building-office" title="Reference Data" subtitle="Shared dropdown lists used across employee profiles and the skill catalog." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <p class="text-sm text-gray-500 dark:text-gray-400">
                These are the shared dropdown lists used across employee profiles and the
                <a href="{{ route('skills.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">skill catalog</a> —
                add to them here so everyone picks from the same options instead of typing free text.
                Positions now have their own page — department, rank, and permissions live at
                <a href="{{ route('positions.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Positions</a>.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ([
                    ['type' => 'department', 'title' => 'Departments', 'icon' => 'building-office', 'items' => $departments, 'countKey' => 'employee_profiles_count', 'accent' => 'violet'],
                    ['type' => 'skill-category', 'title' => 'Skill Categories', 'icon' => 'sparkles', 'items' => $skillCategories, 'countKey' => 'skills_count', 'accent' => 'pink'],
                ] as $group)
                    <x-panel :icon="$group['icon']" :title="$group['title']" :accent="$group['accent']">
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
                    </x-panel>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
