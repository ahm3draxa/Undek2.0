<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="shield-check" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('My Certificates & Letters') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid md:grid-cols-3 gap-4">
                @foreach (\App\Models\Certificate::TYPES as $type => $label)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 space-y-3 transition-colors duration-200">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $label }}</h3>
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
                    </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Issued so far</h3>
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
            </div>
        </div>
    </div>
</x-app-layout>
