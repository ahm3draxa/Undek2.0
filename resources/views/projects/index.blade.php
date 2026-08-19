<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="briefcase" title="Projects" subtitle="Live work — milestones, teams, and delivery status." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card icon="briefcase" label="Total" :value="$stats['total']" accent="violet" />
                <x-stat-card icon="bolt" label="In progress" :value="$stats['in_progress']" accent="amber" />
                <x-stat-card icon="check-circle" label="Delivered" :value="$stats['delivered']" accent="green" />
                <x-stat-card icon="clock" label="Pending assignment" :value="$stats['pending']" accent="pink" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($projects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="block bg-white dark:bg-gray-800 shadow-sm rounded-xl p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-150 relative overflow-hidden">
                        <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
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
                                <div class="h-full" style="width: {{ round($done / $total * 100) }}%; background:linear-gradient(90deg,#7c5cff,#22d3ee)"></div>
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
