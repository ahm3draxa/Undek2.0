<x-guest-layout :title="__('Thank you')">
    <div class="text-center">
        <div class="w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center" style="background:linear-gradient(135deg,rgba(124,92,255,.18),rgba(34,211,238,.18))">
            <x-icon name="check-circle" class="w-8 h-8" style="color:#34e0a1" />
        </div>

        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Thank you for connecting with undek</h1>

        @if (session('quote_title'))
            <p class="text-sm text-gray-400 mt-3">
                We've received your request —
                <span class="text-gray-200 font-medium">&ldquo;{{ session('quote_title') }}&rdquo;</span>
            </p>
        @endif

        <p class="text-sm text-gray-400 mt-3 leading-relaxed">
            A specialist will review your requirements and get back to you with a tailored quotation shortly.
            No sales bots, no generic templates — just people who'll actually build the thing.
        </p>
    </div>

    <div class="mt-8 space-y-3">
        @auth
            <a href="{{ route('quote-requests.show', session('quote_id')) }}">
                <x-auth.button>Track this request &rarr;</x-auth.button>
            </a>
        @else
            <p class="text-xs font-jetbrains uppercase tracking-widest text-gray-500 text-center mb-3">
                Create an account to track progress &amp; chat with your team
            </p>
            <a href="{{ route('register', ['email' => session('quote_email')]) }}">
                <x-auth.button>Create my account &rarr;</x-auth.button>
            </a>
            <a href="{{ route('login', ['email' => session('quote_email')]) }}" class="block">
                <x-auth.ghost-button type="button" class="w-full">
                    I already have an account
                </x-auth.ghost-button>
            </a>
        @endauth
    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
        <a href="{{ route('home') }}" class="hover:text-cyan-300 transition">&larr; Back to undek.com</a>
    </p>
</x-guest-layout>
