<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="users" title="Employees" subtitle="Everyone onboarded, their department, position, and reporting line.">
            <x-slot name="actions">
                <a href="{{ route('employees.create') }}"><x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add employee</x-primary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-card icon="users" label="Total employees" :value="$stats['total']" accent="violet" />
                <x-stat-card icon="sparkles" label="Interns" :value="$stats['interns']" accent="green" />
                <x-stat-card icon="building-office" label="Departments" :value="$stats['departments']" accent="pink" />
                <x-stat-card icon="clock" label="Unassigned position" :value="$stats['unassigned']" accent="amber" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-x-auto transition-colors duration-200">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Position</th>
                            <th class="px-6 py-3">Department</th>
                            <th class="px-6 py-3">Reports to</th>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($employees as $employee)
                            <tr class="text-gray-700 dark:text-gray-200 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-medium">
                                    <div class="flex items-center gap-2.5">
                                        @if ($employee->avatarUrl())
                                            <img src="{{ $employee->avatarUrl() }}" alt="{{ $employee->name }}" class="w-7 h-7 rounded-full object-cover">
                                        @else
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">{{ $employee->initials() }}</div>
                                        @endif
                                        {{ $employee->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->position->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->department->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $employee->employeeProfile->manager->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @foreach ($employee->roles as $role)
                                        <x-role-label :name="$role->name" />@if (! $loop->last), @endif
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <x-action-link href="{{ route('employees.show', $employee) }}" icon="eye">View</x-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No employee profiles yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
