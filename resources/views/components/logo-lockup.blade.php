@props(['glow' => false])

{{-- The full wordmark as one unit: the "un" glyph (u's right leg = n's left leg,
     one continuous stroke) directly followed by "dek" in type — together they
     read "undek". Never pair the glyph with a separate "undek" text span; that
     draws "un" twice. Size the whole thing via font-size (the icon scales with
     it at height:1em); color via `color` (both the icon rim glow and the "dek"
     text pick it up). --}}
@php $gradientId = 'undekMark'.\Illuminate\Support\Str::random(8); @endphp

{{-- Layout is done entirely with inline CSS, not Tailwind classes — this
     component gets used on pages that don't load the compiled Tailwind build
     at all (the marketing landing page has its own standalone stylesheet), so
     "inline-flex"/"items-center" utility classes would silently no-op there,
     and the SVG's own display:block would then force "dek" onto its own line
     underneath instead of sitting beside it. --}}
<span {{ $attributes->merge(['style' => 'display: inline-flex; align-items: center;']) }}>
    <svg viewBox="0 0 94 112" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Undek"
         style="height: 1em; width: auto; display: block; flex-shrink: 0;">
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
    <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; margin-left: 0.1em;">dek</span>
</span>
