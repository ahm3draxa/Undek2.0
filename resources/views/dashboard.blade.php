<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="home" title="Dashboard" />
    </x-slot>

    <div class="py-12"
        x-data="{
            stats: {},
            error: false,
            async refresh() {
                try {
                    const res = await fetch('{{ route('dashboard.stats') }}', {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) throw new Error('bad response');
                    this.stats = await res.json();
                    this.error = false;
                } catch (e) {
                    this.error = true;
                }
            },
            init() {
                this.refresh();
                pollWhileVisible(() => this.refresh(), 60000);
            },
        }"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <p class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 font-jetbrains">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" style="box-shadow:0 0 6px #34e0a1"></span>
                    LIVE &middot; refreshes every minute &middot; server time
                    <span class="text-gray-700 dark:text-gray-300" x-text="stats.server_time ?? '—'"></span>
                </p>
                <p x-show="error" x-cloak class="text-sm text-red-500 dark:text-red-400">Couldn't refresh stats — retrying…</p>
            </div>

            @if ($view === 'executive')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Pending Quote Requests" bind="pending_quote_requests" icon="document-text" accent="amber" />
                    <x-dashboard.stat label="Active Projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Delivered Projects" bind="delivered_projects" icon="check-circle" accent="green" />
                    <x-dashboard.stat label="Employees" bind="total_employees" icon="users" accent="pink" />
                    <x-dashboard.stat label="Clients" bind="total_clients" icon="user-circle" accent="violet" />
                    <x-dashboard.stat label="Pipeline Value" bind="pipeline_value" prefix="$" icon="currency-dollar" accent="green" />
                    <x-dashboard.stat label="Payroll This Month" bind="payroll_this_month" prefix="$" icon="currency-dollar" accent="amber" />
                </div>

                <x-dashboard.quick-links :links="[
                    ['route' => 'quote-requests.index', 'label' => 'Quote Requests', 'icon' => 'document-text'],
                    ['route' => 'projects.index', 'label' => 'Projects', 'icon' => 'briefcase'],
                    ['route' => 'services.index', 'label' => 'Services', 'icon' => 'cog'],
                    ['route' => 'employees.index', 'label' => 'Employees', 'icon' => 'users'],
                    ['route' => 'payroll.index', 'label' => 'Payroll', 'icon' => 'currency-dollar'],
                    ['route' => 'users.index', 'label' => 'Users', 'icon' => 'user-circle'],
                    ['route' => 'roles.index', 'label' => 'Roles & Permissions', 'icon' => 'shield-check'],
                ]" />
            @elseif ($view === 'specialist')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Assigned & Pending" bind="assigned_quote_requests" icon="clock" accent="amber" />
                    <x-dashboard.stat label="Quotations Sent" bind="quotations_sent" icon="document-text" accent="violet" />
                    <x-dashboard.stat label="Accepted" bind="quotations_accepted" icon="check-circle" accent="green" />
                    <x-dashboard.stat label="Awaiting Response" bind="quotations_pending_response" icon="clock" accent="pink" />
                </div>

                <x-dashboard.quick-links :links="[
                    ['route' => 'quote-requests.index', 'label' => 'My Quote Requests', 'icon' => 'document-text'],
                ]" />
            @elseif ($view === 'expert')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Active Projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Upcoming Milestones" bind="upcoming_milestones" icon="clock" accent="amber" />
                    <x-dashboard.stat label="Contributions Logged" bind="contributions_logged" icon="check-circle" accent="green" />
                </div>

                <x-dashboard.quick-links :links="[
                    ['route' => 'projects.index', 'label' => 'My Projects', 'icon' => 'briefcase'],
                ]" />
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Open Requests" bind="open_quote_requests" icon="document-text" accent="amber" />
                    <x-dashboard.stat label="Active Projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Delivered Projects" bind="delivered_projects" icon="check-circle" accent="green" />
                </div>

                <x-dashboard.quick-links :links="[
                    ['route' => 'quote-requests.create', 'label' => 'Request a Quote', 'icon' => 'plus'],
                    ['route' => 'projects.index', 'label' => 'My Projects', 'icon' => 'briefcase'],
                ]" />
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl transition-colors duration-200 flex items-center gap-3 p-5">
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover">
                @else
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">{{ auth()->user()->initials() }}</div>
                @endif
                <span class="text-gray-900 dark:text-gray-100 text-sm">{{ __("You're logged in as :name.", ['name' => auth()->user()->name]) }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
