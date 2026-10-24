<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="briefcase" :title="$project->title" :subtitle="str_replace('_', ' ', ucfirst($project->status))" />
    </x-slot>

    @php
        $user = auth()->user();
        $isManager = $user->can('manage all work');
        $canManage = $isManager || $project->lead_expert_id === $user->id;
        $isTeamMember = $project->teamMembers->contains('user_id', $user->id);
        $canSeeTeamChannel = $isManager || $project->lead_expert_id === $user->id || $isTeamMember;
        // Milestones specifically follow the 'manage milestones' permission (granted via
        // role or position) for anyone actually on the project — not just the lead —
        // separate from $canManage, which stays lead/manager-only for project-level settings.
        $canManageMilestones = $canManage || ($isTeamMember && $user->can('manage milestones') && ! $user->isIntern());
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
                        <p class="text-sm text-gray-500 dark:text-gray-400">Project lead: {{ $project->leadExpert->name ?? 'Unassigned' }}</p>
                    </div>

                    @if ($canManage)
                        <form method="POST" action="{{ route('projects.update', $project) }}" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            @if ($isManager)
                                <select name="lead_expert_id" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                    <option value="">Unassigned</option>
                                    @foreach ($leadCandidates as $expert)
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
                        @if ($leadEscalatedTo)
                            Nobody who can lead projects is available — showing {{ $leadEscalatedTo === 'owner' ? 'owners' : 'admins' }} instead.
                        @elseif ($leadSkillMatched)
                            Project lead options are people who can lead projects and have a skill this project needs.
                        @else
                            No skill match found for this service — showing everyone who can lead projects.
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
                // Who this viewer may delegate a milestone to — everyone on the project for
                // CEO/admin and the lead; otherwise only the people they directly supervise.
                $assignablePeople = $project->assignableBy($user);
                $uploader = app(\App\Services\Uploads\SecureUploader::class);
                $submissionAccept = $uploader->acceptAttribute('milestone_submission');
                $submissionRules = $uploader->profile('milestone_submission');
            @endphp

            <x-panel icon="clock" title="Milestones" accent="amber" subtitle="Assign individual milestones to whoever's actually doing that piece of work.">
                <div class="space-y-2">
                    @forelse ($project->milestones as $milestone)
                        @php
                            // The specific assignee can always act on their own milestone,
                            // even without the broader 'manage milestones' permission.
                            $canActOnThis = $canManageMilestones || $milestone->assigned_to === $user->id;
                        @endphp
                        <div id="milestone-{{ $milestone->id }}" class="scroll-mt-24 border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3 space-y-2 target:ring-2 target:ring-cyan-400">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $milestone->title }}</p>
                                    @if ($milestone->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 whitespace-pre-line">{{ $milestone->description }}</p>
                                    @endif
                                    <div class="flex items-center flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                                        @if ($milestone->due_date)
                                            <p class="text-xs {{ $milestone->isOverdue() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">{{ $milestone->isOverdue() ? 'Overdue · ' : 'Due ' }}{{ $milestone->due_date->format('M j, Y') }}</p>
                                        @endif
                                        @if ($milestone->assignedTo && $canSeeTeamChannel)
                                            <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <x-icon name="user-circle" class="w-3.5 h-3.5" />{{ $milestone->assignedTo->name }}
                                                @if ($milestone->assignedBy)
                                                    <span class="text-gray-400">· assigned by {{ $milestone->assignedBy->name }}</span>
                                                @endif
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

                            @if ($canSeeTeamChannel && $milestone->submissions->isNotEmpty())
                                <div class="space-y-2 pl-3 border-l-2 border-cyan-400/40">
                                    @foreach ($milestone->submissions as $submission)
                                        <div class="text-xs">
                                            <p class="text-gray-500 dark:text-gray-400">
                                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $submission->user->name ?? 'Someone' }}</span>
                                                submitted {{ $submission->created_at->diffForHumans() }}
                                                @if ($submission->marked_done)
                                                    <span class="text-emerald-600 dark:text-emerald-400">· marked done</span>
                                                @endif
                                            </p>
                                            @if ($submission->note)
                                                <p class="text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-line">{{ $submission->note }}</p>
                                            @endif
                                            @if ($submission->files->isNotEmpty())
                                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                                    @foreach ($submission->files as $file)
                                                        <a href="{{ route('milestones.files.show', [$project, $milestone, $file]) }}" target="_blank" rel="noopener"
                                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:ring-1 hover:ring-cyan-400">
                                                            <x-icon name="document-text" class="w-3.5 h-3.5 text-gray-400" />
                                                            <span class="max-w-[14rem] truncate">{{ $file->original_name }}</span>
                                                            <span class="text-gray-400">{{ $uploader->humanSize($file->size) }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($canActOnThis && $milestone->status !== 'completed')
                                <details class="group" @if ($errors->any() && old('submission_for') == $milestone->id) open @endif>
                                    <summary class="cursor-pointer text-xs font-medium text-cyan-600 dark:text-cyan-400 hover:underline list-none">
                                        <span class="group-open:hidden">+ Submit work</span><span class="hidden group-open:inline">− Submit work</span>
                                    </summary>
                                    <form method="POST" action="{{ route('milestones.submit', [$project, $milestone]) }}" enctype="multipart/form-data" class="mt-2 space-y-2">
                                        @csrf
                                        <input type="hidden" name="submission_for" value="{{ $milestone->id }}">
                                        <textarea name="note" rows="3" maxlength="5000" placeholder="What did you do? Links, notes, anything the reviewer should know…"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm">{{ old('submission_for') == $milestone->id ? old('note') : '' }}</textarea>
                                        <input type="file" name="files[]" multiple accept="{{ $submissionAccept }}"
                                            class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200">
                                        <p class="text-[11px] text-gray-400">Up to {{ $submissionRules['max_files'] }} files: images, video, PDF, TXT, Word/Excel/PowerPoint or ZIP.</p>
                                        @if (old('submission_for') == $milestone->id)
                                            <x-input-error :messages="collect($errors->get('note'))->concat($errors->get('files'))->concat(collect($errors->get('files.*'))->flatten())->all()" />
                                        @endif
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                <input type="checkbox" name="mark_done" value="1" class="rounded border-gray-300 dark:border-gray-600" style="accent-color:#34e0a1">
                                                Mark this task as done
                                            </label>
                                            <x-primary-button class="!text-xs">Submit</x-primary-button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            @if ($assignablePeople->isNotEmpty() && (! $milestone->assigned_to || $assignablePeople->contains('id', $milestone->assigned_to)))
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
                                No staff available — showing {{ $expertEscalatedTo === 'owner' ? 'owners' : 'admins' }} instead.
                            @elseif ($skillMatched)
                                Team options are limited to people whose skills match this project's service.
                            @else
                                No skill match found for this service — showing all staff.
                            @endif
                        </p>
                    @endif
                    <ul class="space-y-2">
                        @forelse ($project->teamMembers as $member)
                            <li class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200 border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2">
                                <span>{{ $member->user->name }} <span class="text-gray-400">— {{ $member->role_on_project ?? 'Member' }}@if ($member->user->employeeProfile?->position) · {{ $member->user->employeeProfile->position->name }}@endif</span></span>
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
                                @foreach (\App\Support\Organization::PROJECT_ROLES as $projectRole)
                                    <option value="{{ $projectRole }}">{{ $projectRole }}</option>
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

            @if ($canSeeTeamChannel)
                {{-- Two threads, one on screen at a time: only the visible chat refreshes. --}}
                <div x-data="{ tab: 'client' }" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                    <div class="flex items-center gap-1 px-5 pt-5" role="tablist">
                        <button type="button" role="tab" @click="tab = 'client'" :aria-selected="tab === 'client'"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-space font-semibold transition"
                            :class="tab === 'client' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                            <x-icon name="chat-bubble-left-right" class="w-4 h-4" />Client chat
                        </button>
                        <button type="button" role="tab" @click="tab = 'team'" :aria-selected="tab === 'team'"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-space font-semibold transition"
                            :class="tab === 'team' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                            <x-icon name="users" class="w-4 h-4" />Team channel
                        </button>
                        <span class="ml-auto text-xs text-gray-400 dark:text-gray-500" x-text="tab === 'client' ? 'Visible to the client' : 'Internal — the client never sees this'"></span>
                    </div>
                    <div x-show="tab === 'client'">
                        <x-chat :framed="false" :index-url="route('projects.messages.index', $project)"
                            :store-url="route('projects.messages.store', $project)" />
                    </div>
                    <div x-show="tab === 'team'" x-cloak>
                        <x-chat :framed="false" :index-url="route('projects.team-messages.index', $project)"
                            :store-url="route('projects.team-messages.store', $project)" />
                    </div>
                </div>
            @else
                <x-chat :index-url="route('projects.messages.index', $project)"
                    :store-url="route('projects.messages.store', $project)"
                    title="Project chat" subtitle="You and the Undek team working on this project" />
            @endif
        </div>
    </div>
</x-app-layout>
