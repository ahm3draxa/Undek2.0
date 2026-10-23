@use('App\Support\Organization')
<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="plus" title="Add Employee" subtitle="Onboard a new hire — role, position, and starting compensation." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                <form method="POST" action="{{ route('employees.store') }}" class="space-y-4"
                    x-data="{ compensationType: '{{ old('compensation_type', 'fixed_salary') }}' }">
                    @csrf

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" value="Full name" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role') === $role)>{{ Organization::roleLabel($role) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="position_id" value="Position" />
                            <select id="position_id" name="position_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                <option value="">Select a position</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}" @selected((string) old('position_id') === (string) $position->id)>{{ $position->name }}</option>
                                @endforeach
                            </select>
                            @if ($positions->isEmpty())
                                <p class="text-xs text-gray-400 mt-1">No positions yet — <a href="{{ route('reference-data.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some</a>.</p>
                            @endif
                        </div>
                        <div>
                            <x-input-label for="department_id" value="Department" />
                            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                <option value="">Select a department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @if ($departments->isEmpty())
                                <p class="text-xs text-gray-400 mt-1">No departments yet — <a href="{{ route('reference-data.index') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">add some</a>.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="employment_type" value="Employment type" />
                            <select id="employment_type" name="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                @foreach (Organization::EMPLOYMENT_TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('employment_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="manager_id" value="Reports to (optional)" />
                            <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                <option value="">No manager</option>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected((string) old('manager_id') === (string) $manager->id)>{{ $manager->name }} ({{ $manager->title() }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="hire_date" value="Hire / start date" />
                            <x-text-input id="hire_date" name="hire_date" type="date" class="block mt-1 w-full" :value="old('hire_date')" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="End date (internship / fixed-term, optional)" />
                            <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" :value="old('end_date')" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="compensation_type" value="Compensation type" />
                        <select id="compensation_type" name="compensation_type" x-model="compensationType" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach (\App\Models\EmployeeProfile::COMPENSATION_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('compensation_type', 'fixed_salary') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('compensation_type')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'fixed_salary'" x-cloak>
                        <x-input-label for="base_salary" value="Base salary" />
                        <x-text-input id="base_salary" name="base_salary" type="number" step="0.01" class="block mt-1 w-full" :value="old('base_salary')" />
                        <x-input-error :messages="$errors->get('base_salary')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'hourly'" x-cloak>
                        <x-input-label for="hourly_rate" value="Hourly rate (USD)" />
                        <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" class="block mt-1 w-full" :value="old('hourly_rate')" />
                        <x-input-error :messages="$errors->get('hourly_rate')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'fixed_project_price'" x-cloak>
                        <x-input-label for="fixed_project_price" value="Fixed price per project (USD)" />
                        <x-text-input id="fixed_project_price" name="fixed_project_price" type="number" step="0.01" class="block mt-1 w-full" :value="old('fixed_project_price')" />
                        <x-input-error :messages="$errors->get('fixed_project_price')" class="mt-2" />
                    </div>

                    <div x-show="compensationType === 'project_percentage'" x-cloak>
                        <x-input-label for="project_percentage" value="Percentage of project value (%)" />
                        <x-text-input id="project_percentage" name="project_percentage" type="number" step="0.01" min="0" max="100" class="block mt-1 w-full" :value="old('project_percentage')" />
                        <x-input-error :messages="$errors->get('project_percentage')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bio" value="Bio (optional)" />
                        <textarea id="bio" name="bio" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">{{ old('bio') }}</textarea>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public')) class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                        Show on the public "Team" page
                    </label>

                    <x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add employee</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
