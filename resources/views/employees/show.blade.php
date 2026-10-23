<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                @if ($employee->avatarUrl())
                    <img src="{{ $employee->avatarUrl() }}" alt="{{ $employee->name }}" class="w-10 h-10 rounded-xl object-cover">
                @else
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold text-white" style="background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 4px 14px rgba(124,92,255,.35)">{{ $employee->initials() }}</div>
                @endif
                <div>
                    <h2 class="font-space font-bold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $employee->name }}</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $employee->employeeProfile->position->name ?? 'No position set' }}</p>
                </div>
            </div>
            <x-action-link href="{{ route('employees.edit', $employee) }}" icon="pencil" class="text-sm">Edit profile</x-action-link>
        </div>
    </x-slot>

    @php $profile = $employee->employeeProfile; @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                <div><div class="text-gray-400">Position</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->position->name ?? '—' }}</div></div>
                <div><div class="text-gray-400">Department</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->department->name ?? '—' }}</div></div>
                @if ($clientRatingCount > 0)
                    <div>
                        <div class="text-gray-400">Client rating</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100 text-amber-500">{{ str_repeat('★', round($clientRating)) }}{{ str_repeat('☆', 5 - round($clientRating)) }}</div>
                        <div class="text-xs text-gray-400">{{ number_format($clientRating, 1) }}/5 across {{ $clientRatingCount }} project(s)</div>
                    </div>
                @endif
                <div>
                    <div class="text-gray-400">Compensation</div>
                    <div class="font-medium text-gray-800 dark:text-gray-100">
                        @if ($profile->compensationValue() !== null)
                            @if ($profile->compensation_type === 'project_percentage')
                                {{ number_format($profile->compensationValue(), 2) }}% of project value
                            @elseif ($profile->compensation_type === 'hourly')
                                {{ number_format($profile->compensationValue(), 2) }}/hr
                            @elseif ($profile->compensation_type === 'fixed_project_price')
                                {{ number_format($profile->compensationValue(), 2) }} per project
                            @else
                                {{ number_format($profile->compensationValue(), 2) }}/yr
                            @endif
                        @else
                            —
                        @endif
                    </div>
                    <div class="text-xs text-gray-400">{{ \App\Models\EmployeeProfile::COMPENSATION_TYPES[$profile->compensation_type] ?? '—' }}</div>
                </div>
                <div><div class="text-gray-400">Equity shares</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->equity_shares ?? '—' }}</div></div>
                <div><div class="text-gray-400">Role</div><div class="font-medium text-gray-800 dark:text-gray-100">
                    @foreach ($employee->roles as $role)
                        <x-role-label :name="$role->name" />@if (! $loop->last), @endif
                    @endforeach
                </div></div>
                <div><div class="text-gray-400">Hire date</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ optional($profile->hire_date)->format('M j, Y') ?? '—' }}</div></div>
                @if ($profile->end_date)
                    <div><div class="text-gray-400">End date</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->end_date->format('M j, Y') }}</div></div>
                @endif
                <div>
                    <div class="text-gray-400">Reports to</div>
                    <div class="font-medium text-gray-800 dark:text-gray-100">
                        @if ($profile->manager)
                            <a href="{{ route('employees.show', $profile->manager) }}" class="hover:underline text-cyan-600 dark:text-cyan-400">{{ $profile->manager->name }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                @php $reports = $employee->directReports()->get(); @endphp
                @if ($reports->isNotEmpty())
                    <div class="col-span-2 md:col-span-4">
                        <div class="text-gray-400 mb-1">Direct reports</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($reports as $report)
                                <a href="{{ route('employees.show', $report) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-cyan-50 dark:hover:bg-cyan-900/30 hover:text-cyan-700 dark:hover:text-cyan-300">{{ $report->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Skills -->
            <x-panel icon="sparkles" title="Skills" accent="pink">
                @if ($missingSkills->isNotEmpty())
                    <div class="flex items-start gap-2.5 rounded-lg border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-900/20 px-3.5 py-2.5 -mt-1">
                        <x-icon name="bolt" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            <span class="font-medium">Skill gap:</span> their position expects
                            {{ $missingSkills->pluck('name')->join(', ', ' and ') }}, not yet on this profile.
                        </p>
                    </div>
                @endif
                <div class="flex flex-wrap gap-2">
                    @forelse ($employee->skills as $skill)
                        <span class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-violet-50 to-cyan-50 dark:from-violet-900/25 dark:to-cyan-900/25 border border-violet-100 dark:border-violet-800/40 text-gray-700 dark:text-gray-200">
                            {{ $skill->name }}
                            <span class="text-gray-400 dark:text-gray-500">· {{ ucfirst($skill->pivot->level) }}</span>
                            <form method="POST" action="{{ route('employees.skills.destroy', [$employee, $skill]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="w-4 h-4 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-red-500 transition" aria-label="Remove skill">&times;</button>
                            </form>
                        </span>
                    @empty
                        <p class="text-gray-400 text-sm">No skills recorded yet.</p>
                    @endforelse
                </div>

                @if ($availableSkills->isEmpty())
                    <p class="text-sm text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">
                        No skills exist yet — <a href="{{ route('skills.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some to the master list</a> first.
                    </p>
                @else
                    <form method="POST" action="{{ route('employees.skills.store', $employee) }}" class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="skill_id" required class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            @foreach ($availableSkills as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                            @endforeach
                        </select>
                        <select name="level" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate" selected>Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                        <x-secondary-button class="mt-3"><x-icon name="plus" class="w-3.5 h-3.5" />Add</x-secondary-button>
                    </form>
                @endif
            </x-panel>

            <!-- Position history -->
            <x-panel icon="briefcase" title="Position history" accent="violet">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @forelse ($employee->promotions as $promotion)
                        <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2 flex items-center justify-between gap-3">
                            <span>
                                {{ $promotion->oldPosition->name ?? 'New hire' }} &rarr; <span class="font-medium">{{ $promotion->newPosition->name ?? '—' }}</span>
                                @if ($promotion->direction === 'transfer' && $promotion->newPosition?->department)
                                    <span class="text-gray-400">({{ $promotion->newPosition->department->name }})</span>
                                @endif
                                <span class="text-gray-400">— {{ $promotion->effective_date->format('M j, Y') }}</span>
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @class([
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $promotion->direction === 'promotion',
                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $promotion->direction === 'demotion',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $promotion->direction === 'lateral',
                                    'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300' => $promotion->direction === 'transfer',
                                ])">{{ ucfirst($promotion->direction ?? 'promotion') }}</span>
                        </li>
                    @empty
                        <p class="text-gray-400">No position changes recorded.</p>
                    @endforelse
                </ul>
            </x-panel>

            <!-- Promote / demote -->
            <x-panel icon="check-circle" title="Promote / demote" accent="green"
                subtitle="Stays within {{ $profile->department->name ?? 'the current department' }} — the direction is worked out automatically from rank.">
                @if (! $profile->position)
                    <p class="text-sm text-gray-400">No starting position yet — use Transfer below to assign one.</p>
                @elseif ($ladderPositions->isEmpty())
                    <p class="text-sm text-gray-400">No other positions in this department yet — <a href="{{ route('positions.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add one</a> first.</p>
                @else
                    <form method="POST" action="{{ route('employees.promote', $employee) }}" class="grid grid-cols-2 gap-3">
                        @csrf
                        <select name="new_position_id" required class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                            <option value="">New position in {{ $profile->department->name ?? 'department' }}…</option>
                            @foreach ($ladderPositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }} (rank {{ $position->rank }}){{ $position->rank > $profile->position->rank ? ' — senior' : ($position->rank < $profile->position->rank ? ' — junior' : ' — lateral') }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" name="new_salary" placeholder="New salary (optional)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="date" name="effective_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <x-secondary-button class="col-span-2 justify-center">Record change</x-secondary-button>
                    </form>
                @endif
            </x-panel>

            <!-- Transfer -->
            <x-panel icon="arrow-right" title="Transfer" accent="pink" subtitle="Move to a different department and assign a position there manually.">
                @if ($departments->isEmpty())
                    <p class="text-sm text-gray-400">No departments exist yet — <a href="{{ route('reference-data.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some</a> first.</p>
                @else
                    <form method="POST" action="{{ route('employees.transfer', $employee) }}" class="grid grid-cols-2 gap-3"
                        x-data="{ deptId: '' }">
                        @csrf
                        <select name="department_id" x-model="deptId" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                            <option value="">New department…</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <select name="new_position_id" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                            <option value="">New position…</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" x-show="deptId === '{{ $position->department_id }}'" x-cloak>{{ $position->name }} (rank {{ $position->rank }})</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" name="new_salary" placeholder="New salary (optional)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="date" name="effective_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <x-secondary-button class="col-span-2 justify-center">Transfer</x-secondary-button>
                    </form>
                @endif
            </x-panel>

            <!-- Secondary positions -->
            <x-panel icon="sparkles" title="Secondary positions" accent="amber" subtitle="Held alongside their primary position — each contributes its own permissions.">
                <div class="flex flex-wrap gap-2">
                    @forelse ($employee->secondaryPositions as $secondary)
                        <span class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-50 to-pink-50 dark:from-amber-900/25 dark:to-pink-900/25 border border-amber-100 dark:border-amber-800/40 text-gray-700 dark:text-gray-200">
                            {{ $secondary->name }}
                            @if ($secondary->department)
                                <span class="text-gray-400 dark:text-gray-500">· {{ $secondary->department->name }}</span>
                            @endif
                            <form method="POST" action="{{ route('employees.positions.destroy', [$employee, $secondary]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="w-4 h-4 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-red-500 transition" aria-label="Remove position">&times;</button>
                            </form>
                        </span>
                    @empty
                        <p class="text-gray-400 text-sm">No secondary positions.</p>
                    @endforelse
                </div>

                @if ($addablePositions->isEmpty())
                    <p class="text-sm text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">No other positions available to add.</p>
                @else
                    <form method="POST" action="{{ route('employees.positions.store', $employee) }}" class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="position_id" required class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            @foreach ($addablePositions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}{{ $position->department ? ' · '.$position->department->name : '' }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button class="mt-3"><x-icon name="plus" class="w-3.5 h-3.5" />Add</x-secondary-button>
                    </form>
                @endif
            </x-panel>

            <!-- Compensation -->
            @can('manage payroll')
                <x-panel icon="currency-dollar" title="Compensation" accent="green">
                    <x-slot name="actions">
                        <x-action-link href="{{ route('compensation.show', $employee) }}" icon="currency-dollar" class="text-sm">Manage compensation</x-action-link>
                    </x-slot>
                    <p class="text-xs text-gray-400 -mt-2">Rate, history, and adjustments now live in Finance.</p>
                </x-panel>
            @endcan

            <!-- Contributions -->
            <x-panel icon="check-circle" title="Contributions" accent="amber">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @forelse ($employee->contributions as $contribution)
                        <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2">
                            <span class="font-medium">{{ $contribution->title }}</span>
                            @if ($contribution->project) <span class="text-gray-400">— {{ $contribution->project->title }}</span> @endif
                            <span class="text-gray-400">— {{ $contribution->contribution_date->format('M j, Y') }}</span>
                        </li>
                    @empty
                        <p class="text-gray-400">No contributions logged.</p>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('employees.contributions.store', $employee) }}" class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    @csrf
                    <input type="text" name="title" required placeholder="Title" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                    <input type="date" name="contribution_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                    <textarea name="description" rows="2" placeholder="Description (optional)" class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"></textarea>
                    <x-secondary-button class="col-span-2 justify-center">Log contribution</x-secondary-button>
                </form>
            </x-panel>

            <!-- Payroll -->
            @can('manage payroll')
                <x-panel icon="currency-dollar" title="Payroll adjustments" accent="violet">
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        @forelse ($employee->payrollAdjustments as $adjustment)
                            <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2 flex justify-between">
                                <span>{{ str_replace('_', ' ', ucfirst($adjustment->type)) }}</span>
                                <span class="font-medium">{{ $adjustment->currency }} {{ number_format($adjustment->amount, 2) }}</span>
                            </li>
                        @empty
                            <p class="text-gray-400">No payroll adjustments yet.</p>
                        @endforelse
                    </ul>

                    <form method="POST" action="{{ route('payroll.store', $employee) }}" class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="type" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            @foreach (['salary_change', 'bonus', 'share_grant', 'deduction', 'reimbursement'] as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" name="amount" required placeholder="Amount" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                        <input type="date" name="effective_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="text" name="reason" placeholder="Reason (optional)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <x-secondary-button class="col-span-2 justify-center">Add adjustment</x-secondary-button>
                    </form>
                </x-panel>
            @endcan
        </div>
    </div>
</x-app-layout>
