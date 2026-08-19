@php
    $primaryHolders = $position->employeeProfiles->pluck('user')->filter();
    $secondaryHolders = $position->users;
    $allHolders = $primaryHolders->concat($secondaryHolders)->unique('id');
@endphp

<div class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2.5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-jetbrains font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $position->rank }}</span>
            <div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $position->name }}</div>
                <div class="text-xs text-gray-400">{{ $position->permissions->count() }} {{ Str::plural('permission', $position->permissions->count()) }}</div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <x-action-link href="{{ route('positions.edit', $position) }}" icon="pencil" class="text-xs">Edit</x-action-link>
            @if ($allHolders->isEmpty())
                <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('Remove this position?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-1 text-red-500 hover:text-red-600 hover:underline text-xs"><x-icon name="trash" class="w-3.5 h-3.5" />Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="pl-10 mt-2 space-y-2">
        @if ($allHolders->isEmpty())
            <p class="text-xs text-gray-400 italic">No one holds this position yet.</p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($primaryHolders as $person)
                    <a href="{{ route('employees.show', $person) }}" class="inline-flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-violet-50 to-cyan-50 dark:from-violet-900/25 dark:to-cyan-900/25 border border-violet-100 dark:border-violet-800/40 text-gray-700 dark:text-gray-200 hover:border-cyan-400/60">
                        @if ($person->avatarUrl())
                            <img src="{{ $person->avatarUrl() }}" alt="{{ $person->name }}" class="w-5 h-5 rounded-full object-cover">
                        @else
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">{{ $person->initials() }}</span>
                        @endif
                        {{ $person->name }}
                    </a>
                @endforeach
                @foreach ($secondaryHolders as $person)
                    <a href="{{ route('employees.show', $person) }}" class="inline-flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 text-gray-700 dark:text-gray-200 hover:border-amber-400/60">
                        @if ($person->avatarUrl())
                            <img src="{{ $person->avatarUrl() }}" alt="{{ $person->name }}" class="w-5 h-5 rounded-full object-cover">
                        @else
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-semibold text-white shrink-0" style="background:linear-gradient(135deg,#ffb020,#ff5da2)">{{ $person->initials() }}</span>
                        @endif
                        {{ $person->name }}
                        <span class="text-gray-400 text-[10px]">secondary</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($position->skills->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 mt-2 pl-10">
            @foreach ($position->skills as $skill)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $skill->name }}</span>
            @endforeach
        </div>
    @endif
</div>
