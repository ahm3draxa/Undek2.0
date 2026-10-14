<x-guest-layout :title="__('Unsubscribe')">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">{{ $done ? 'You\'re unsubscribed' : 'Unsubscribe from Undek updates' }}</h1>
        <p class="text-sm text-gray-400 mt-1.5">
            @if ($done)
                <span class="text-gray-200">{{ $email }}</span> won't receive marketing emails from Undek any more.
                Emails about your own projects and account are not affected.
            @else
                Stop marketing emails to <span class="text-gray-200">{{ $email }}</span>?
            @endif
        </p>
    </div>

    @unless ($done)
        <form method="POST" action="{{ $action }}">
            <x-auth.button>Unsubscribe</x-auth.button>
        </form>
    @endunless
</x-guest-layout>
