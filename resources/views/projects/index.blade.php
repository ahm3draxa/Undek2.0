<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="briefcase" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($projects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="block bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 hover:ring-2 hover:ring-indigo-400 transition">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $project->title }}</h3>
                            <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                {{ str_replace('_', ' ', ucfirst($project->status)) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Client: {{ $project->client->name ?? '—' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lead: {{ $project->leadExpert->name ?? 'Unassigned' }}</p>
                        @php
                            $total = $project->milestones->count();
                            $done = $project->milestones->where('status', 'completed')->count();
                        @endphp
                        @if ($total)
                            <div class="mt-3 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full bg-indigo-500" style="width: {{ round($done / $total * 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $done }}/{{ $total }} milestones</p>
                        @endif
                    </a>
                @empty
                    <p class="text-gray-400 col-span-full text-center py-10">No projects yet.</p>
                @endforelse
            </div>

            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
