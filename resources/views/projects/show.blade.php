<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="briefcase" class="w-5 h-5" style="color:#7c5cff" />
            {{ $project->title }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['ceo', 'admin']);
        $canManage = $isManager || $project->lead_expert_id === $user->id;
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-3 transition-colors duration-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $project->title }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Client: {{ $project->client->name ?? '—' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lead expert: {{ $project->leadExpert->name ?? 'Unassigned' }}</p>
                    </div>

                    @if ($canManage)
                        <form method="POST" action="{{ route('projects.update', $project) }}" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            @if ($isManager)
                                <select name="lead_expert_id" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                    <option value="">Unassigned</option>
                                    @foreach ($experts as $expert)
                                        <option value="{{ $expert->id }}" @selected($project->lead_expert_id === $expert->id)>{{ $expert->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="lead_expert_id" value="{{ $project->lead_expert_id }}">
                            @endif
                            <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                @foreach (['pending_assignment', 'in_progress', 'completed', 'delivered', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected($project->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                @if ($isManager)
                    <p class="text-xs text-gray-400">
                        @if ($skillMatched)
                            Lead expert options are limited to people whose skills match this project's service.
                        @else
                            No skill match found for this service — showing everyone with an expert/employee role.
                        @endif
                    </p>
                @endif
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $project->description }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Milestones</h3>
                </div>

                <div class="space-y-2">
                    @forelse ($project->milestones as $milestone)
                        <div class="flex items-center justify-between gap-3 border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $milestone->title }}</p>
                                @if ($milestone->due_date)
                                    <p class="text-xs text-gray-400">Due {{ $milestone->due_date->format('M j, Y') }}</p>
                                @endif
                            </div>
                            @if ($canManage)
                                <form method="POST" action="{{ route('milestones.update', [$project, $milestone]) }}">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs">
                                        @foreach (['pending', 'in_progress', 'completed'] as $status)
                                            <option value="{{ $status }}" @selected($milestone->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($milestone->status)) }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No milestones yet.</p>
                    @endforelse
                </div>

                @if ($canManage)
                    <form method="POST" action="{{ route('milestones.store', $project) }}" class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <input type="text" name="title" required placeholder="New milestone title"
                            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm mt-3">
                        <input type="date" name="due_date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm mt-3">
                        <x-secondary-button class="mt-3">Add</x-secondary-button>
                    </form>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Team</h3>
                @if ($canManage)
                    <p class="text-xs text-gray-400 -mt-2">
                        @if ($skillMatched)
                            Team options are limited to people whose skills match this project's service.
                        @else
                            No skill match found for this service — showing everyone with an expert/employee role.
                        @endif
                    </p>
                @endif
                <ul class="space-y-2">
                    @forelse ($project->teamMembers as $member)
                        <li class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2">
                            <span>{{ $member->user->name }} <span class="text-gray-400">— {{ $member->role_on_project ?? 'Team member' }}</span></span>
                            @if ($canManage)
                                <form method="POST" action="{{ route('projects.team.destroy', [$project, $member]) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline text-xs">Remove</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <p class="text-sm text-gray-400">No team members assigned yet.</p>
                    @endforelse
                </ul>

                @if ($canManage)
                    <form method="POST" action="{{ route('projects.team.store', $project) }}" class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="user_id" required class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            @foreach ($experts as $expert)
                                <option value="{{ $expert->id }}">{{ $expert->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="role_on_project" placeholder="Role (optional)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                        <x-secondary-button class="mt-3">Add</x-secondary-button>
                    </form>
                @endif
            </div>

            @if (in_array($project->status, ['completed', 'delivered']) || $project->feedbacks->isNotEmpty())
                @php
                    $clientFeedback = $project->clientFeedback();
                    $undekFeedback = $project->undekFeedback();
                    $canGiveClientFeedback = $user->id === $project->client_id;
                    $canGiveUndekFeedback = $isManager || $project->lead_expert_id === $user->id;
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-6 transition-colors duration-200">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Feedback</h3>

                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <div class="text-xs font-medium text-gray-400 mb-2">Client &rarr; Undek</div>
                            @if ($clientFeedback)
                                <div class="text-amber-400 text-sm mb-1">{{ str_repeat('★', $clientFeedback->rating) }}{{ str_repeat('☆', 5 - $clientFeedback->rating) }}</div>
                                @if ($clientFeedback->comment)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $clientFeedback->comment }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">{{ $clientFeedback->author->name ?? 'Client' }} — {{ $clientFeedback->created_at->format('M j, Y') }}</p>
                            @elseif ($canGiveClientFeedback)
                                <form method="POST" action="{{ route('projects.feedback.store', $project) }}" class="space-y-2">
                                    @csrf
                                    <select name="rating" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                        <option value="">Rate this project…</option>
                                        @foreach ([5,4,3,2,1] as $n)
                                            <option value="{{ $n }}">{{ str_repeat('★', $n) }} ({{ $n }}/5)</option>
                                        @endforeach
                                    </select>
                                    <textarea name="comment" rows="2" placeholder="How was working with us? (optional)" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"></textarea>
                                    <x-secondary-button>Submit feedback</x-secondary-button>
                                </form>
                            @else
                                <p class="text-sm text-gray-400">No client feedback yet.</p>
                            @endif
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-400 mb-2">Undek &rarr; Client</div>
                            @if ($undekFeedback)
                                <div class="text-amber-400 text-sm mb-1">{{ str_repeat('★', $undekFeedback->rating) }}{{ str_repeat('☆', 5 - $undekFeedback->rating) }}</div>
                                @if ($undekFeedback->comment)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $undekFeedback->comment }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">{{ $undekFeedback->author->name ?? 'Team' }} — {{ $undekFeedback->created_at->format('M j, Y') }}</p>
                            @elseif ($canGiveUndekFeedback)
                                <form method="POST" action="{{ route('projects.feedback.store', $project) }}" class="space-y-2">
                                    @csrf
                                    <select name="rating" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                        <option value="">Rate this client…</option>
                                        @foreach ([5,4,3,2,1] as $n)
                                            <option value="{{ $n }}">{{ str_repeat('★', $n) }} ({{ $n }}/5)</option>
                                        @endforeach
                                    </select>
                                    <textarea name="comment" rows="2" placeholder="Notes for the record (optional)" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"></textarea>
                                    <x-secondary-button>Submit feedback</x-secondary-button>
                                </form>
                            @else
                                <p class="text-sm text-gray-400">No feedback from Undek yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <x-chat :index-url="route('projects.messages.index', $project)"
                :store-url="route('projects.messages.store', $project)"
                title="Project chat" />
        </div>
    </div>
</x-app-layout>
