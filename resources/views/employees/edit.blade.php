<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="pencil" :title="__('Edit Profile — :name', ['name' => $employee->name])" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                @php $profile = $employee->employeeProfile; @endphp
                <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-4"
                    x-data="{ compensationType: '{{ old('compensation_type', $profile->compensation_type ?? 'fixed_salary') }}' }">
                    @csrf @method('PUT')

                    @can('manage users')
                        <div>
                            <x-input-label for="role" value="Role" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                @php $currentRole = $employee->roles->first()?->name; @endphp
                                @foreach ([
                                    'intern' => 'Intern',
                                    'employee' => 'Employee',
                                    'specialist' => 'Specialist',
                                    'expert' => 'Expert',
                                    'admin' => 'Admin',
                                    'ceo' => 'CEO',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('role', $currentRole) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Ordered as a typical growth path — roles aren't otherwise ranked, each has its own permission set on the <a href="{{ route('roles.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Roles &amp; Permissions</a> page. Changing this replaces their current role.</p>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                    @endcan

                    <div class="rounded-md bg-gray-50 dark:bg-gray-900/40 px-4 py-3 text-sm text-gray-600 dark:text-gray-300 flex items-center justify-between">
                        <span>
                            <span class="text-gray-400">Position:</span> {{ $profile->position->name ?? 'None yet' }}
                            <span class="text-gray-400 ml-2">Department:</span> {{ $profile->department->name ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400">Change via Promote/Transfer on their profile</span>
                    </div>

                    <div>
                        <x-input-label for="employment_type" value="Employment type" />
                        <select id="employment_type" name="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contractor' => 'Contractor', 'intern' => 'Intern'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $profile->employment_type ?? 'full_time') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="hire_date" value="Hire / start date" />
                            <x-text-input id="hire_date" name="hire_date" type="date" class="block mt-1 w-full" :value="old('hire_date', optional($profile->hire_date ?? null)->format('Y-m-d'))" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="End date (internship / fixed-term, optional)" />
                            <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" :value="old('end_date', optional($profile->end_date ?? null)->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="compensation_type" value="Compensation type" />
                        <select id="compensation_type" name="compensation_type" x-model="compensationType" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach (\App\Models\EmployeeProfile::COMPENSATION_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('compensation_type', $profile->compensation_type ?? 'fixed_salary') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('compensation_type')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'fixed_salary'" x-cloak>
                        <x-input-label for="base_salary" value="Base salary" />
                        <x-text-input id="base_salary" name="base_salary" type="number" step="0.01" class="block mt-1 w-full" :value="old('base_salary', $profile->base_salary ?? '')" />
                        <x-input-error :messages="$errors->get('base_salary')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'hourly'" x-cloak>
                        <x-input-label for="hourly_rate" value="Hourly rate (USD)" />
                        <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" class="block mt-1 w-full" :value="old('hourly_rate', $profile->hourly_rate ?? '')" />
                        <x-input-error :messages="$errors->get('hourly_rate')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'fixed_project_price'" x-cloak>
                        <x-input-label for="fixed_project_price" value="Fixed price per project (USD)" />
                        <x-text-input id="fixed_project_price" name="fixed_project_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('fixed_project_price', $profile->fixed_project_price ?? '')" />
                        <x-input-error :messages="$errors->get('fixed_project_price')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'project_percentage'" x-cloak>
                        <x-input-label for="project_percentage" value="Percentage of project value (%)" />
                        <x-text-input id="project_percentage" name="project_percentage" type="number" step="0.01" min="0" max="100" class="block mt-1 w-full" :value="old('project_percentage', $profile->project_percentage ?? '')" />
                        <x-input-error :messages="$errors->get('project_percentage')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="equity_shares" value="Equity shares" />
                        <x-text-input id="equity_shares" name="equity_shares" type="number" step="0.0001" class="block mt-1 w-full" :value="old('equity_shares', $profile->equity_shares ?? '')" />
                    </div>

                    <div>
                        <x-input-label for="manager_id" value="Reports to" />
                        <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            <option value="">No manager</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected((string) old('manager_id', $profile->manager_id ?? '') === (string) $manager->id)>{{ $manager->name }} ({{ $manager->employeeProfile->position->name ?? ($manager->roles->first()?->name === 'ceo' ? 'CEO' : \Illuminate\Support\Str::of($manager->roles->first()?->name ?? '')->replace('_', ' ')->title()) }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bio" value="Bio" />
                        <textarea id="bio" name="bio" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">{{ old('bio', $profile->bio ?? '') }}</textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $profile->is_public ?? false)) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                        Show on the public "Team" page
                    </label>

                    <x-primary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />Save profile</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
