<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="shield-check" title="My Certificates & Letters" subtitle="Self-serve, verifiable documents generated from your profile." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            @php
                $typeIcons = [
                    \App\Models\Certificate::INTERNSHIP_CERTIFICATE => ['icon' => 'sparkles', 'accent' => 'pink'],
                    \App\Models\Certificate::EXPERIENCE_LETTER => ['icon' => 'briefcase', 'accent' => 'violet'],
                    \App\Models\Certificate::EARNINGS_LETTER => ['icon' => 'currency-dollar', 'accent' => 'green'],
                ];
            @endphp

            <div class="grid md:grid-cols-3 gap-4">
                @foreach (\App\Models\Certificate::TYPES as $type => $label)
                    <x-panel :icon="$typeIcons[$type]['icon']" :title="$label" :accent="$typeIcons[$type]['accent']">
                        @if ($eligibility[$type])
                            <p class="text-xs text-gray-400">Generates a fresh, dated copy each time — older copies stay valid.</p>
                            <form method="POST" action="{{ route('certificates.generate', $type) }}">
                                @csrf
                                <x-secondary-button class="w-full justify-center text-xs">Generate</x-secondary-button>
                            </form>
                        @else
                            <p class="text-xs text-gray-400">
                                @if ($type === \App\Models\Certificate::INTERNSHIP_CERTIFICATE)
                                    Available only for internship employment type.
                                @elseif ($type === \App\Models\Certificate::EARNINGS_LETTER)
                                    Set a compensation rate on your profile first.
                                @else
                                    Not available yet.
                                @endif
                            </p>
                        @endif
                    </x-panel>
                @endforeach
            </div>

            <x-panel icon="clock" title="Issued so far" accent="amber">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    @forelse ($certificates as $certificate)
                        <li class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-2 flex items-center justify-between gap-3">
                            <span>
                                {{ \App\Models\Certificate::TYPES[$certificate->type] ?? $certificate->type }}
                                <span class="text-gray-400 font-jetbrains text-xs">— {{ $certificate->code }} — {{ $certificate->issued_at->format('M j, Y') }}</span>
                            </span>
                            <x-action-link href="{{ route('certificates.show', $certificate) }}" icon="document-text" class="text-xs">View</x-action-link>
                        </li>
                    @empty
                        <p class="text-gray-400">Nothing generated yet.</p>
                    @endforelse
                </ul>
            </x-panel>
        </div>
    </div>
</x-app-layout>
