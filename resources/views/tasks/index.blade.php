<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="check-circle" title="My Tasks" subtitle="Milestones assigned to you, and the ones you've handed out." />
    </x-slot>

    @php
        $statusStyles = [
            'pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            'in_progress' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="flex justify-end">
                <a href="{{ route('tasks.index', $showDone ? [] : ['done' => 1]) }}" class="text-sm text-cyan-600 dark:text-cyan-400 hover:underline">
                    {{ $showDone ? 'Hide completed tasks' : 'Show completed tasks' }}
                </a>
            </div>

            @foreach ([
                ['title' => 'Assigned to me', 'icon' => 'user-circle', 'accent' => 'amber', 'tasks' => $assignedToMe, 'empty' => 'Nothing assigned to you right now.', 'mine' => true],
                ['title' => 'Assigned by me', 'icon' => 'users', 'accent' => 'violet', 'tasks' => $assignedByMe, 'empty' => 'You haven\'t handed out any open tasks.', 'mine' => false],
            ] as $section)
                <x-panel :icon="$section['icon']" :title="$section['title'].' ('.$section['tasks']->count().')'" :accent="$section['accent']">
                    <div class="space-y-2">
                        @forelse ($section['tasks'] as $task)
                            <a href="{{ route('projects.show', $task->project_id) }}#milestone-{{ $task->id }}"
                                class="flex flex-wrap items-center justify-between gap-3 border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $task->project->title ?? 'Project' }}
                                        &middot;
                                        @if ($section['mine'])
                                            from {{ $task->assignedBy->name ?? 'the project lead' }}
                                        @else
                                            to {{ $task->assignedTo->name ?? '—' }}
                                        @endif
                                        @if ($task->submissions_count)
                                            &middot; {{ $task->submissions_count }} submission{{ $task->submissions_count === 1 ? '' : 's' }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($task->due_date)
                                        <span class="text-xs {{ $task->isOverdue() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                            {{ $task->isOverdue() ? 'Overdue · ' : 'Due ' }}{{ $task->due_date->format('M j, Y') }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$task->status] ?? $statusStyles['pending'] }}">
                                        {{ str_replace('_', ' ', ucfirst($task->status)) }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-400">{{ $section['empty'] }}</p>
                        @endforelse
                    </div>
                </x-panel>
            @endforeach
        </div>
    </div>
</x-app-layout>
