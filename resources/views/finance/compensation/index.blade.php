<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="currency-dollar" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Compensation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
                <p class="text-xs text-gray-400 mb-4">Every onboarded employee, their department/position, and current standing compensation. Open one to review its full adjustment history or apply an increase/decrease.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2">Employee</th>
                                <th class="py-2">Department</th>
                                <th class="py-2">Position</th>
                                <th class="py-2">Type</th>
                                <th class="py-2 text-right">Current rate</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($employees as $employee)
                                @php $profile = $employee->employeeProfile; @endphp
                                <tr>
                                    <td class="py-3 flex items-center gap-2.5">
                                        @if ($employee->avatarUrl())
                                            <img src="{{ $employee->avatarUrl() }}" alt="{{ $employee->name }}" class="w-7 h-7 rounded-full object-cover">
                                        @else
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">{{ $employee->initials() }}</div>
                                        @endif
                                        <span class="text-gray-800 dark:text-gray-100 font-medium">{{ $employee->name }}</span>
                                    </td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $profile->department->name ?? '—' }}</td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $profile->position->name ?? '—' }}</td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ \App\Models\EmployeeProfile::COMPENSATION_TYPES[$profile->compensation_type] ?? '—' }}</td>
                                    <td class="py-3 text-right font-medium text-gray-800 dark:text-gray-100">
                                        @if ($profile->compensationValue() !== null)
                                            @if ($profile->compensation_type === 'project_percentage')
                                                {{ number_format($profile->compensationValue(), 2) }}%
                                            @else
                                                ${{ number_format($profile->compensationValue(), 2) }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        <x-action-link href="{{ route('compensation.show', $employee) }}" icon="chevron-right" class="text-xs">Manage</x-action-link>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-gray-400">No onboarded employees yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
