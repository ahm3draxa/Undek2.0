@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-xs text-rose-400 space-y-1 mt-2']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5">
                <span class="mt-0.5">&#9679;</span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
