@use('App\Support\Organization')
<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="shield-check" title="Roles & Teams" subtitle="What each role allows, and which tools each team gets." />
    </x-slot>

    @php
        $descriptions = Organization::PERMISSIONS;
        $roleHelp = [
            'owner' => 'Founders. Can do everything, always — this role can\'t be edited, so the business can never be locked out.',
            'admin' => 'Runs day-to-day operations: people, services and all quotes and projects. Can\'t change owner accounts.',
            'staff' => 'Undek team members. What they can do beyond this comes from their team (department).',
            'client' => 'Customers. They see their own quote requests and projects only.',
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="rounded-xl border border-cyan-200 dark:border-cyan-500/30 bg-cyan-50 dark:bg-cyan-500/10 px-5 py-4 text-sm text-cyan-900 dark:text-cyan-100 space-y-1">
                <p><strong>How access works:</strong> a person's <strong>role</strong> sets their base access, and each <strong>team</strong> they belong to adds that team's tools.</p>
                <p class="text-cyan-800/80 dark:text-cyan-200/80">Teams come from job positions — a primary position, plus any secondary ones. Job titles and skills never grant access by themselves.</p>
            </div>

            <h2 class="font-space text-lg font-semibold text-gray-900 dark:text-gray-100">Roles</h2>

            @foreach ($roles as $role)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 transition-colors duration-200 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100"><x-role-label :name="$role->name" /></h3>
                                <span class="text-xs font-jetbrains text-gray-400">{{ $role->users_count }} {{ Str::plural('person', $role->users_count) }}</span>
                            </div>
                            @isset($roleHelp[$role->name])
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $roleHelp[$role->name] }}</p>
                            @endisset
                        </div>

                        @if (! array_key_exists($role->name, Organization::ROLES) && $role->users_count === 0)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this leftover role?')">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1 text-red-500 hover:text-red-600 hover:underline text-sm"><x-icon name="trash" class="w-4 h-4" />Delete</button>
                            </form>
                        @endif
                    </div>

                    @unless ($role->name === Organization::OWNER)
                        <form method="POST" action="{{ route('roles.update', $role) }}">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 mb-4">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            @checked($role->permissions->contains('name', $permission->name))
                                            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                        <span>{{ $descriptions[$permission->name] ?? ucfirst($permission->name) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-secondary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />Save {{ Organization::roleLabel($role->name) }} permissions</x-secondary-button>
                        </form>
                    @endunless
                </div>
            @endforeach

            <h2 class="font-space text-lg font-semibold text-gray-900 dark:text-gray-100 pt-4">Team tools</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 -mt-4">Everyone in a team gets its tools. Changes apply to current members straight away.</p>

            @foreach ($departments as $department)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#34e0a1,#22d3ee)"></div>
                    <div class="flex items-center gap-2 mb-4">
                        <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100">{{ $department->name }}</h3>
                        <span class="text-xs font-jetbrains text-gray-400">{{ $department->members()->count() }} {{ Str::plural('person', $department->members()->count()) }}</span>
                    </div>
                    <form method="POST" action="{{ route('roles.departments.update', $department) }}">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 mb-4">
                            @foreach ($permissions->reject(fn ($p) => in_array($p->name, ['manage users', 'manage roles'], true)) as $permission)
                                <label class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                        @checked($department->permissions->contains('name', $permission->name))
                                        class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-emerald-600">
                                    <span>{{ $descriptions[$permission->name] ?? ucfirst($permission->name) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-secondary-button><x-icon name="check-circle" class="w-3.5 h-3.5" />Save {{ $department->name }} tools</x-secondary-button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
