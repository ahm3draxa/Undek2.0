<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2.5 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                @if ($employee->avatarUrl())
                    <img src="{{ $employee->avatarUrl() }}" alt="{{ $employee->name }}" class="w-7 h-7 rounded-full object-cover">
                @else
                    <x-icon name="user-circle" class="w-5 h-5" style="color:#7c5cff" />
                @endif
                {{ $employee->name }}
            </h2>
            <x-action-link href="{{ route('employees.edit', $employee) }}" icon="pencil" class="text-sm">Edit profile</x-action-link>
        </div>
    </x-slot>

    @php $profile = $employee->employeeProfile; @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm transition-colors duration-200">
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
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    <x-icon name="sparkles" class="w-4 h-4" style="color:#7c5cff" />
                    Skills
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($employee->skills as $skill)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            {{ $skill->name }}
                            <span class="text-gray-400 dark:text-gray-500">· {{ ucfirst($skill->pivot->level) }}</span>
                            <form method="POST" action="{{ route('employees.skills.destroy', [$employee, $skill]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-gray-400 hover:text-red-500" aria-label="Remove skill">&times;</button>
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
                            <option value="expert">Expert</option>
                        </select>
                        <x-secondary-button class="mt-3"><x-icon name="plus" class="w-3.5 h-3.5" />Add</x-secondary-button>
                    </form>
                @endif
            </div>

            <!-- Promotion / demotion -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Position changes</h3>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @forelse ($employee->promotions as $promotion)
                        <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2 flex items-center justify-between gap-3">
                            <span>
                                {{ $promotion->oldPosition->name ?? 'New hire' }} &rarr; <span class="font-medium">{{ $promotion->newPosition->name ?? '—' }}</span>
                                <span class="text-gray-400">— {{ $promotion->effective_date->format('M j, Y') }}</span>
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @class([
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => ($promotion->direction ?? 'promotion') === 'promotion',
                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => ($promotion->direction ?? 'promotion') === 'demotion',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => ($promotion->direction ?? 'promotion') === 'lateral',
                                ])">{{ ucfirst($promotion->direction ?? 'promotion') }}</span>
                        </li>
                    @empty
                        <p class="text-gray-400">No position changes recorded.</p>
                    @endforelse
                </ul>

                @if ($positions->isEmpty())
                    <p class="text-sm text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">
                        No positions exist yet — <a href="{{ route('reference-data.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some</a> first.
                    </p>
                @else
                    <form method="POST" action="{{ route('employees.promote', $employee) }}" class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        @csrf
                        <select name="direction" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            <option value="promotion">Promotion</option>
                            <option value="demotion">Demotion</option>
                            <option value="lateral">Lateral move</option>
                        </select>
                        <select name="new_position_id" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                            <option value="">New position…</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" name="new_salary" placeholder="New salary (optional)" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm mt-3">
                        <input type="date" name="effective_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <x-secondary-button class="col-span-2 justify-center">Record change</x-secondary-button>
                    </form>
                @endif
            </div>

            <!-- Compensation -->
            @can('manage payroll')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex items-center justify-between transition-colors duration-200">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Compensation</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Rate, history, and adjustments now live in Finance.</p>
                    </div>
                    <x-action-link href="{{ route('compensation.show', $employee) }}" icon="currency-dollar" class="text-sm">Manage compensation</x-action-link>
                </div>
            @endcan

            <!-- Contributions -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Contributions</h3>
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
            </div>

            <!-- Payroll -->
            @can('manage payroll')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Payroll adjustments</h3>
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
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
