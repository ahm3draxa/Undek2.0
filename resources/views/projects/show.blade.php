<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="briefcase" :title="$project->title" :subtitle="str_replace('_', ' ', ucfirst($project->status))" />
    </x-slot>

    @php
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['ceo', 'admin']);
        $canManage = $isManager || $project->lead_expert_id === $user->id;
        $isTeamMember = $project->teamMembers->contains('user_id', $user->id);
        $canSeeTeamChannel = $isManager || $project->lead_expert_id === $user->id || $isTeamMember;
        // Milestones specifically follow the 'manage milestones' permission (granted via
        // role or position) for anyone actually on the project — not just the lead —
        // separate from $canManage, which stays lead/manager-only for project-level settings.
        $canManageMilestones = $canManage || ($isTeamMember && $user->can('manage milestones'));
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-3 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
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
                                        <option value="{{ $expert->id }}" @selected($project->lead_expert_id === $expert->id)>{{ $expert->name }} — {{ $expert->email }}</option>
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
                        @if ($expertEscalatedTo)
                            No expert or employee available — escalated to {{ $expertEscalatedTo === 'ceo' ? 'the CEO' : 'admins' }} instead.
                        @elseif ($skillMatched)
                            Lead expert options are limited to people whose skills match this project's service.
                        @else
                            No skill match found for this service — showing everyone with an expert, employee, or intern role.
                        @endif
                    </p>
                @endif
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $project->description }}</p>
            </div>

            @php
                $milestoneStatuses = [
                    'pending' => ['icon' => 'clock', 'label' => 'Pending', 'active' => 'bg-gray-500 text-white', 'idle' => 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'],
                    'in_progress' => ['icon' => 'bolt', 'label' => 'In progress', 'active' => 'bg-amber-500 text-white', 'idle' => 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'],
                    'completed' => ['icon' => 'check-circle', 'label' => 'Completed', 'active' => 'bg-emerald-500 text-white', 'idle' => 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'],
                ];
                // Who a milestone can actually be delegated to — the lead plus whoever's
                // currently on the team (including any interns added for this project).
                $assignablePeople = collect([$project->leadExpert])->filter()->concat($project->teamMembers->pluck('user'))->unique('id');
            @endphp

            <x-panel icon="clock" title="Milestones" accent="amber" subtitle="Assign individual milestones to whoever's actually doing that piece of work.">
                <div class="space-y-2">
                    @forelse ($project->milestones as $milestone)
                        @php
                            // The specific assignee can always act on their own milestone,
                            // even without the broader 'manage milestones' permission.
                            $canActOnThis = $canManageMilestones || $milestone->assigned_to === $user->id;
                        @endphp
                        <div class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3 space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $milestone->title }}</p>
                                    <div class="flex items-center flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                                        @if ($milestone->due_date)
                                            <p class="text-xs text-gray-400">Due {{ $milestone->due_date->format('M j, Y') }}</p>
                                        @endif
                                        @if ($milestone->assignedTo && $canSeeTeamChannel)
                                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <x-icon name="user-circle" class="w-3.5 h-3.5" />{{ $milestone->assignedTo->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if ($canActOnThis)
                                    <form method="POST" action="{{ route('milestones.update', [$project, $milestone]) }}" class="flex items-center gap-1 shrink-0">
                                        @csrf @method('PATCH')
                                        @foreach ($milestoneStatuses as $status => $meta)
                                            <button type="submit" name="status" value="{{ $status }}" title="{{ $meta['label'] }}" aria-label="{{ $meta['label'] }}"
                                                class="w-7 h-7 rounded-md flex items-center justify-center transition {{ $milestone->status === $status ? $meta['active'] : $meta['idle'] }}">
                                                <x-icon name="{{ $meta['icon'] }}" class="w-4 h-4" />
                                            </button>
                                        @endforeach
                                    </form>
                                @else
                                    @php $meta = $milestoneStatuses[$milestone->status]; @endphp
                                    <span class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 {{ $meta['active'] }}" title="{{ $meta['label'] }}">
                                        <x-icon name="{{ $meta['icon'] }}" class="w-4 h-4" />
                                    </span>
                                @endif
                            </div>

                            @if ($canManage && $assignablePeople->isNotEmpty())
                                <form method="POST" action="{{ route('milestones.assign', [$project, $milestone]) }}" class="flex items-center gap-1.5">
                                    @csrf @method('PATCH')
                                    <x-icon name="user-circle" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                    <select name="assigned_to" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs">
                                        <option value="">Unassigned</option>
                                        @foreach ($assignablePeople as $person)
                                            <option value="{{ $person->id }}" @selected($milestone->assigned_to === $person->id)>{{ $person->name }} — {{ $person->email }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No milestones yet.</p>
                    @endforelse
                </div>

                @if ($canManageMilestones)
                    <form method="POST" action="{{ route('milestones.store', $project) }}" class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <div class="flex gap-3">
                            <input type="text" name="title" required placeholder="New milestone title"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm mt-3">
                            <input type="date" name="due_date" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm mt-3">
                            <x-secondary-button class="mt-3">Add</x-secondary-button>
                        </div>
                        @if ($assignablePeople->isNotEmpty())
                            <select name="assigned_to" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs">
                                <option value="">Assign to… (optional)</option>
                                @foreach ($assignablePeople as $person)
                                    <option value="{{ $person->id }}">{{ $person->name }} — {{ $person->email }}</option>
                                @endforeach
                            </select>
                        @endif
                    </form>
                @endif
            </x-panel>

            {{-- Internal-only — the client never sees who's actually staffed on the project. --}}
            @if ($canSeeTeamChannel)
                <x-panel icon="users" title="Team" accent="violet" subtitle="Internal only — not visible to the client.">
                    @if ($canManage)
                        <p class="text-xs text-gray-400 -mt-2">
                            @if ($expertEscalatedTo)
                                No expert or employee available — escalated to {{ $expertEscalatedTo === 'ceo' ? 'the CEO' : 'admins' }} instead.
                            @elseif ($skillMatched)
                                Team options are limited to people whose skills match this project's service.
                            @else
                                No skill match found for this service — showing everyone with an expert, employee, or intern role.
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
                                    <option value="{{ $expert->id }}">{{ $expert->name }} — {{ $expert->email }}</option>
                                @endforeach
                            </select>
                            <select name="role_on_project" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                                <option value="">Role (optional)</option>
                                @foreach ($positionsByDepartment as $department => $positions)
                                    <optgroup label="{{ $department }}">
                                        @foreach ($positions as $position)
                                            <option value="{{ $position->name }}">{{ $position->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <x-secondary-button class="mt-3">Add</x-secondary-button>
                        </form>
                    @endif
                </x-panel>
            @endif

            @if (in_array($project->status, ['completed', 'delivered']) || $project->feedbacks->isNotEmpty())
                @php
                    $clientFeedback = $project->clientFeedback();
                    $undekFeedback = $project->undekFeedback();
                    $canGiveClientFeedback = $user->id === $project->client_id;
                    $canGiveUndekFeedback = $isManager || $project->lead_expert_id === $user->id;
                @endphp
                <x-panel icon="sparkles" title="Feedback" accent="pink">
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
                </x-panel>
            @endif

            <x-chat :index-url="route('projects.messages.index', $project)"
                :store-url="route('projects.messages.store', $project)"
                title="Project chat" />

            @if ($canSeeTeamChannel)
                <x-chat :index-url="route('projects.team-messages.index', $project)"
                    :store-url="route('projects.team-messages.store', $project)"
                    title="Team channel — internal only" />
            @endif
        </div>
    </div>
</x-app-layout>
