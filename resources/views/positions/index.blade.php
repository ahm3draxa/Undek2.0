<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="briefcase" title="Positions" subtitle="Each department's own rank ladder, from junior to senior.">
            <x-slot name="actions">
                <a href="{{ route('positions.create') }}"><x-primary-button><x-icon name="plus" class="w-3.5 h-3.5" />Add position</x-primary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            @foreach ($departments as $department)
                <x-panel icon="building-office" :title="$department->name" accent="violet">
                    <div class="space-y-2">
                        @forelse ($department->positions as $position)
                            @include('positions._row')
                        @empty
                            <p class="text-sm text-gray-400">No positions in this department yet.</p>
                        @endforelse
                    </div>
                </x-panel>
            @endforeach

            @if ($unassigned->isNotEmpty())
                <x-panel icon="cog" title="Company-wide (no department)" accent="pink">
                    <div class="space-y-2">
                        @foreach ($unassigned as $position)
                            @include('positions._row')
                        @endforeach
                    </div>
                </x-panel>
            @endif
        </div>
    </div>
</x-app-layout>
