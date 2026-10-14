<x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    <x-icon name="home" class="w-5 h-5 shrink-0" />{{ __('Dashboard') }}
</x-sidebar-link>
<x-sidebar-link :href="route('quote-requests.index')" :active="request()->routeIs('quote-requests.*')">
    <x-icon name="document-text" class="w-5 h-5 shrink-0" />{{ __('Quotes') }}
</x-sidebar-link>
<x-sidebar-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
    <x-icon name="briefcase" class="w-5 h-5 shrink-0" />{{ __('Projects') }}
</x-sidebar-link>
@unless (auth()->user()->hasRole('client') && ! auth()->user()->employeeProfile)
    <x-sidebar-link :href="route('tasks.index')" :active="request()->routeIs('tasks.*')">
        <x-icon name="check-circle" class="w-5 h-5 shrink-0" />{{ __('My Tasks') }}
    </x-sidebar-link>
@endunless
@if (auth()->user()->employeeProfile)
    <x-sidebar-link :href="route('certificates.index')" :active="request()->routeIs('certificates.*')">
        <x-icon name="shield-check" class="w-5 h-5 shrink-0" />{{ __('My Certificates') }}
    </x-sidebar-link>
@endif
@can('view inbox')
    <x-sidebar-link :href="route('inbox.index')" :active="request()->routeIs('inbox.*')">
        <x-icon name="envelope" class="w-5 h-5 shrink-0" />{{ __('Inbox') }}
        @php $inboxUnread = \App\Models\InboxMessage::unread()->count(); @endphp
        @if ($inboxUnread)
            <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full text-white" style="background:linear-gradient(135deg,#ff5da2,#7c5cff)">{{ $inboxUnread > 99 ? '99+' : $inboxUnread }}</span>
        @endif
    </x-sidebar-link>
@endcan
@can('manage-marketing')
    <x-sidebar-link :href="route('marketing.index')" :active="request()->routeIs('marketing.*')">
        <x-icon name="sparkles" class="w-5 h-5 shrink-0" />{{ __('Marketing') }}
    </x-sidebar-link>
@endcan
@can('manage services')
    <x-sidebar-link :href="route('services.index')" :active="request()->routeIs('services.*')">
        <x-icon name="cog" class="w-5 h-5 shrink-0" />{{ __('Services') }}
    </x-sidebar-link>
@endcan
@can('manage employees')
    <x-sidebar-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
        <x-icon name="users" class="w-5 h-5 shrink-0" />{{ __('Employees') }}
    </x-sidebar-link>
    <x-sidebar-link :href="route('positions.index')" :active="request()->routeIs('positions.*')">
        <x-icon name="briefcase" class="w-5 h-5 shrink-0" />{{ __('Positions') }}
    </x-sidebar-link>
    <x-sidebar-link :href="route('reference-data.index')" :active="request()->routeIs('reference-data.*')">
        <x-icon name="building-office" class="w-5 h-5 shrink-0" />{{ __('Reference Data') }}
    </x-sidebar-link>
@endcan
@can('manage payroll')
    <x-sidebar-link :href="route('payroll.index')" :active="request()->routeIs('payroll.*')">
        <x-icon name="currency-dollar" class="w-5 h-5 shrink-0" />{{ __('Payroll') }}
    </x-sidebar-link>
    <x-sidebar-link :href="route('compensation.index')" :active="request()->routeIs('compensation.*')">
        <x-icon name="currency-dollar" class="w-5 h-5 shrink-0" />{{ __('Compensation') }}
    </x-sidebar-link>
    <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
        <x-icon name="document-text" class="w-5 h-5 shrink-0" />{{ __('Reports') }}
    </x-sidebar-link>
@endcan
@can('manage skills')
    <x-sidebar-link :href="route('skills.index')" :active="request()->routeIs('skills.*')">
        <x-icon name="sparkles" class="w-5 h-5 shrink-0" />{{ __('Skills') }}
    </x-sidebar-link>
@endcan
@can('manage users')
    <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">
        <x-icon name="shield-check" class="w-5 h-5 shrink-0" />{{ __('Users') }}
    </x-sidebar-link>
@endcan
@can('manage roles')
    <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
        <x-icon name="shield-check" class="w-5 h-5 shrink-0" />{{ __('Roles') }}
    </x-sidebar-link>
@endcan
