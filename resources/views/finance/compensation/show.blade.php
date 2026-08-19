<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2.5 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                @if ($employee->avatarUrl())
                    <img src="{{ $employee->avatarUrl() }}" alt="{{ $employee->name }}" class="w-7 h-7 rounded-full object-cover">
                @else
                    <x-icon name="currency-dollar" class="w-5 h-5" style="color:#7c5cff" />
                @endif
                {{ $employee->name }} — Compensation
            </h2>
            <x-action-link href="{{ route('compensation.index') }}" icon="arrow-right" icon-class="rotate-180" class="text-sm">
                Back to all employees
            </x-action-link>
        </div>
    </x-slot>

    @php $profile = $employee->employeeProfile; @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                <div><div class="text-gray-400">Department</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->department->name ?? '—' }}</div></div>
                <div><div class="text-gray-400">Position</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ $profile->position->name ?? '—' }}</div></div>
                <div><div class="text-gray-400">Compensation type</div><div class="font-medium text-gray-800 dark:text-gray-100">{{ \App\Models\EmployeeProfile::COMPENSATION_TYPES[$profile->compensation_type] ?? '—' }}</div></div>
                <div>
                    <div class="text-gray-400">Current rate</div>
                    <div class="font-medium text-gray-800 dark:text-gray-100">
                        @if ($profile->compensationValue() !== null)
                            @if ($profile->compensation_type === 'project_percentage')
                                {{ number_format($profile->compensationValue(), 2) }}%
                            @elseif ($profile->compensation_type === 'hourly')
                                ${{ number_format($profile->compensationValue(), 2) }}/hr
                            @elseif ($profile->compensation_type === 'fixed_project_price')
                                ${{ number_format($profile->compensationValue(), 2) }}/project
                            @else
                                ${{ number_format($profile->compensationValue(), 2) }}/yr
                            @endif
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            <x-panel icon="clock" title="Adjustment history" accent="pink">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @forelse ($employee->compensationIncrements as $increment)
                        <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2 flex justify-between">
                            <span>
                                @php $sign = ($increment->direction ?? 'increase') === 'decrease' ? '-' : '+'; @endphp
                                <span class="{{ ($increment->direction ?? 'increase') === 'decrease' ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400' }} font-medium">
                                    {{ $sign }}{{ number_format($increment->value, 2) }}{{ $increment->type === 'percentage' ? '%' : '' }}
                                </span>
                                on {{ str_replace('_', ' ', $increment->field) }}
                                <span class="text-gray-400">— {{ $increment->effective_date->format('M j, Y') }} by {{ $increment->createdBy->name ?? '—' }}</span>
                                @if ($increment->notes)
                                    <span class="block text-xs text-gray-400 mt-0.5">{{ $increment->notes }}</span>
                                @endif
                            </span>
                            <span class="font-medium whitespace-nowrap">{{ number_format($increment->old_value, 2) }} &rarr; {{ number_format($increment->new_value, 2) }}</span>
                        </li>
                    @empty
                        <p class="text-gray-400">No adjustments recorded yet.</p>
                    @endforelse
                </ul>
            </x-panel>

            <x-panel icon="bolt" title="Apply an adjustment" accent="amber" subtitle="Applies to this employee's current compensation type — {{ \App\Models\EmployeeProfile::COMPENSATION_TYPES[$profile->compensation_type] ?? '—' }}.">

                <form method="POST" action="{{ route('compensation.store', $employee) }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <select name="direction" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>
                    <select name="type" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed amount</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="value" required placeholder="Value" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <input type="date" name="effective_date" required class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <x-secondary-button class="col-span-2 justify-center">Apply adjustment</x-secondary-button>
                </form>
            </x-panel>
        </div>
    </div>
</x-app-layout>
