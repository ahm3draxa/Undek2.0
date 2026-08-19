@props(['glow' => false])

{{-- The "un" ligature — u's right leg and n's left leg share one stroke.
     Gradient id is unique per instance so this can render more than once
     on the same page (desktop nav + mobile nav are both in the DOM at once)
     without colliding <linearGradient> ids. --}}
@php $gradientId = 'undekMark'.\Illuminate\Support\Str::random(8); @endphp

<svg viewBox="0 0 94 112" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Undek" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="7" y1="0" x2="87" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#7c5cff" />
            <stop offset="1" stop-color="#22d3ee" />
        </linearGradient>
    </defs>
    @if ($glow)
        <path d="M15,24 L15,82 A16,16 0 0 0 47,82 L47,24 A16,16 0 0 1 79,24 L79,82"
              stroke="url(#{{ $gradientId }})" stroke-width="17" stroke-linecap="round" stroke-linejoin="round"
              opacity=".55" filter="blur(2.5px)" />
    @endif
    <path d="M15,24 L15,82 A16,16 0 0 0 47,82 L47,24 A16,16 0 0 1 79,24 L79,82"
          stroke="url(#{{ $gradientId }})" stroke-width="15" stroke-linecap="round" stroke-linejoin="round" />
</svg>
