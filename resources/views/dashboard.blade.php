@php
    $user = auth()->user();
    $firstName = \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name;

    $quickLinks = collect([
        ['route' => 'projects.create', 'label' => 'New project', 'icon' => 'plus', 'can' => 'create projects'],
        ['route' => 'quote-requests.create', 'label' => 'Request a quote', 'icon' => 'plus', 'if' => ! $user->isStaff()],
        ['route' => 'quote-requests.index', 'label' => $user->isStaff() ? 'Quote requests' : 'My requests', 'icon' => 'document-text'],
        ['route' => 'projects.index', 'label' => $user->isStaff() ? 'Projects' : 'My projects', 'icon' => 'briefcase'],
        ['route' => 'tasks.index', 'label' => 'My tasks', 'icon' => 'check-circle', 'if' => $user->isStaff()],
        ['route' => 'inbox.index', 'label' => 'Inbox', 'icon' => 'envelope', 'can' => 'view any inbox'],
        ['route' => 'marketing.index', 'label' => 'Marketing', 'icon' => 'sparkles', 'can' => 'send marketing'],
        ['route' => 'employees.index', 'label' => 'Employees', 'icon' => 'users', 'can' => 'manage employees'],
        ['route' => 'payroll.index', 'label' => 'Payroll', 'icon' => 'currency-dollar', 'can' => 'manage payroll'],
        ['route' => 'roles.index', 'label' => 'Roles & Teams', 'icon' => 'shield-check', 'can' => 'manage roles'],
    ])->filter(fn ($link) => ($link['if'] ?? true) && (! isset($link['can']) || $user->can($link['can'])))->values()->all();

    $rowClass = 'flex items-center justify-between gap-3 px-4 py-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/40 transition';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="home" title="Dashboard" />
    </x-slot>

    <div class="py-10"
        x-data="{
            stats: @js($initialStats),
            // The server runs on UTC; greet and time-stamp in the visitor's own clock.
            greeting: (h => h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening')(new Date().getHours()),
            updatedAt: new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }),
            error: false,
            loading: false,
            poller: null,
            async refresh() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('dashboard.stats') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('bad response');
                    this.stats = await res.json();
                    this.updatedAt = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    this.error = false;
                } catch (e) {
                    this.error = true;
                } finally {
                    this.loading = false;
                }
            },
            init() {
                // First stats are already on the page; refresh every 5 minutes while looked at.
                this.poller = pollWhileVisible(() => this.refresh(), 300000);
            },
        }"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Greeting --}}
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">{{ $user->initials() }}</div>
                    @endif
                    <div>
                        <h2 class="font-space text-2xl font-bold text-gray-900 dark:text-gray-100"><span x-text="greeting">Welcome</span>, {{ $firstName }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->isStaff() ? $user->title() : 'Client' }} · <span x-text="new Date().toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' })"></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                    <span x-show="!error">Updated <span x-text="updatedAt"></span></span>
                    <span x-show="error" x-cloak class="text-red-500 dark:text-red-400">Couldn't refresh — will retry</span>
                    <button type="button" @click="poller.now()" :disabled="loading"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-cyan-400 transition disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" :class="loading && 'animate-spin'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            @if ($view === 'executive')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Pending quote requests" bind="pending_quote_requests" icon="document-text" accent="amber" />
                    <x-dashboard.stat label="Active projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Delivered projects" bind="delivered_projects" icon="check-circle" accent="green" />
                    <x-dashboard.stat label="Clients" bind="total_clients" icon="user-circle" accent="pink" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-dashboard.stat label="Employees" bind="total_employees" icon="users" accent="violet" />
                    <x-dashboard.stat label="Pipeline value" bind="pipeline_value" prefix="$" icon="currency-dollar" accent="green" />
                    @can('manage payroll')
                        <x-dashboard.stat label="Payroll this month" bind="payroll_this_month" prefix="$" icon="currency-dollar" accent="amber" />
                    @endcan
                </div>
            @elseif ($view === 'specialist')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-dashboard.stat label="Assigned & pending" bind="assigned_quote_requests" icon="clock" accent="amber" />
                    <x-dashboard.stat label="Quotations sent" bind="quotations_sent" icon="document-text" accent="violet" />
                    <x-dashboard.stat label="Accepted" bind="quotations_accepted" icon="check-circle" accent="green" />
                    <x-dashboard.stat label="Awaiting response" bind="quotations_pending_response" icon="clock" accent="pink" />
                </div>
            @elseif ($view === 'expert')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-dashboard.stat label="Active projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Upcoming milestones" bind="upcoming_milestones" icon="clock" accent="amber" />
                    <x-dashboard.stat label="Contributions logged" bind="contributions_logged" icon="check-circle" accent="green" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-dashboard.stat label="Open requests" bind="open_quote_requests" icon="document-text" accent="amber" />
                    <x-dashboard.stat label="Active projects" bind="active_projects" icon="briefcase" accent="violet" />
                    <x-dashboard.stat label="Delivered projects" bind="delivered_projects" icon="check-circle" accent="green" />
                </div>
            @endif

            <x-dashboard.quick-links :links="$quickLinks" />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Needs attention (owners/admins) --}}
                @if ($attention)
                    <x-panel icon="bolt" title="Needs attention" accent="amber">
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <a href="{{ route('projects.index') }}" class="rounded-lg border border-gray-100 dark:border-gray-700 px-4 py-3 hover:border-amber-300 transition">
                                <div class="text-2xl font-space font-bold {{ $attention['projects_without_lead'] ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600' }}">{{ $attention['projects_without_lead'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">active {{ Str::plural('project', $attention['projects_without_lead']) }} without a lead</div>
                            </a>
                            <a href="{{ route('projects.index') }}" class="rounded-lg border border-gray-100 dark:border-gray-700 px-4 py-3 hover:border-red-300 transition">
                                <div class="text-2xl font-space font-bold {{ $attention['overdue_milestones'] ? 'text-red-500' : 'text-gray-300 dark:text-gray-600' }}">{{ $attention['overdue_milestones'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">overdue {{ Str::plural('milestone', $attention['overdue_milestones']) }}</div>
                            </a>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Quote requests nobody is pricing yet</p>
                        <div class="-mx-2">
                            @forelse ($attention['unassigned_quotes'] as $quote)
                                <a href="{{ route('quote-requests.show', $quote) }}" class="{{ $rowClass }}">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $quote->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $quote->service->name ?? 'Service' }} · {{ $quote->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400 shrink-0">Assign →</span>
                                </a>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-400">All caught up — every request has someone on it.</p>
                            @endforelse
                        </div>
                    </x-panel>
                @endif

                {{-- My tasks (staff) --}}
                @if ($user->isStaff())
                    <x-panel icon="check-circle" title="My tasks" accent="violet">
                        <div class="-mx-2">
                            @forelse ($myTasks as $task)
                                <a href="{{ route('projects.show', $task->project_id) }}#milestone-{{ $task->id }}" class="{{ $rowClass }}">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $task->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $task->project->title ?? 'Project' }}</p>
                                    </div>
                                    @if ($task->due_date)
                                        <span class="text-xs shrink-0 {{ $task->isOverdue() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                            {{ $task->isOverdue() ? 'Overdue' : 'Due '.$task->due_date->format('M j') }}
                                        </span>
                                    @endif
                                </a>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-400">Nothing assigned to you right now.</p>
                            @endforelse
                        </div>
                        @if ($myTasks->isNotEmpty())
                            <a href="{{ route('tasks.index') }}" class="inline-block mt-2 text-xs font-medium text-cyan-600 dark:text-cyan-400 hover:underline">All my tasks →</a>
                        @endif
                    </x-panel>
                @endif

                {{-- Quotes I'm pricing (Sales) --}}
                @if ($myQuotes->isNotEmpty())
                    <x-panel icon="document-text" title="Quotes you're pricing" accent="green">
                        <div class="-mx-2">
                            @foreach ($myQuotes as $quote)
                                <a href="{{ route('quote-requests.show', $quote) }}" class="{{ $rowClass }}">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $quote->title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $quote->service->name ?? 'Service' }}</p>
                                    </div>
                                    <span class="text-xs shrink-0 px-2 py-0.5 rounded-full {{ $quote->status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $quote->status === 'pending' ? 'Needs a quote' : 'Awaiting client' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </x-panel>
                @endif

                {{-- Client: their active projects --}}
                @if ($view === 'client')
                    <x-panel icon="briefcase" title="Your active projects" accent="violet">
                        <div class="-mx-2">
                            @forelse ($clientItems as $project)
                                <a href="{{ route('projects.show', $project) }}" class="{{ $rowClass }}">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $project->title }}</p>
                                    <span class="text-xs text-gray-400 shrink-0">{{ str_replace('_', ' ', ucfirst($project->status)) }}</span>
                                </a>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-400">
                                    No active projects yet. <a href="{{ route('quote-requests.create') }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">Request a quote</a> to get started.
                                </p>
                            @endforelse
                        </div>
                    </x-panel>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
