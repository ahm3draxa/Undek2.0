<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="envelope" title="Inbox" subtitle="Mail sent to the company addresses — checked automatically every few minutes." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            @if ($configured->filter()->isEmpty())
                <div class="rounded-xl border border-amber-300 dark:border-amber-500/40 bg-amber-50 dark:bg-amber-500/10 px-5 py-4 text-sm text-amber-800 dark:text-amber-200">
                    No mailbox is connected yet. Add <code class="font-mono">INBOX_INFO_PASSWORD</code> and
                    <code class="font-mono">INBOX_SUPPORT_PASSWORD</code> to the server's <code class="font-mono">.env</code> to start receiving mail here.
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5 text-sm">
                    @php $tab = 'px-3 py-1.5 rounded-md transition'; @endphp
                    <a href="{{ route('inbox.index', array_filter(['unread' => $unreadOnly ?: null, 'q' => $search ?: null])) }}"
                        class="{{ $tab }} {{ $mailbox === null ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        All
                        @if ($unreadCounts->sum())
                            <span class="ml-1 text-xs font-semibold">{{ $unreadCounts->sum() }}</span>
                        @endif
                    </a>
                    @foreach ($mailboxes as $box)
                        <a href="{{ route('inbox.index', array_filter(['mailbox' => $box, 'unread' => $unreadOnly ?: null, 'q' => $search ?: null])) }}"
                            class="{{ $tab }} {{ $mailbox === $box ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ config("inbox.mailboxes.$box.address") }}
                            @if ($unreadCounts[$box] ?? 0)
                                <span class="ml-1 text-xs font-semibold">{{ $unreadCounts[$box] }}</span>
                            @endif
                            @unless ($configured[$box])
                                <span class="ml-1 text-xs text-amber-500" title="Not connected">●</span>
                            @endunless
                        </a>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('inbox.index') }}" class="flex items-center gap-2">
                        @if ($mailbox)<input type="hidden" name="mailbox" value="{{ $mailbox }}">@endif
                        <input type="search" name="q" value="{{ $search }}" placeholder="Search sender or subject"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm w-56">
                        <label class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="unread" value="1" @checked($unreadOnly) onchange="this.form.submit()" class="rounded border-gray-300 dark:border-gray-600" style="accent-color:#7c5cff">
                            Unread
                        </label>
                    </form>
                    <form method="POST" action="{{ route('inbox.refresh') }}">
                        @csrf
                        <x-secondary-button type="submit">Check now</x-secondary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($messages as $message)
                    <a href="{{ route('inbox.show', $message) }}" class="flex items-start gap-4 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $message->isUnread() ? 'bg-cyan-500' : 'bg-transparent' }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="truncate text-sm {{ $message->isUnread() ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-200' }}">
                                    {{ $message->from_name ?: $message->from_email ?: 'Unknown sender' }}
                                </p>
                                <span class="shrink-0 text-xs text-gray-400" title="{{ $message->received_at?->format('M j, Y g:i A') }}">
                                    {{ $message->received_at?->isToday() ? $message->received_at->format('g:i A') : $message->received_at?->format('M j') }}
                                </span>
                            </div>
                            <p class="truncate text-sm {{ $message->isUnread() ? 'font-medium text-gray-800 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300' }}">
                                {{ $message->subject }}
                                @if ($message->attachments_count)
                                    <x-icon name="paper-clip" class="inline w-3.5 h-3.5 text-gray-400 -mt-0.5" />
                                @endif
                            </p>
                            <p class="truncate text-xs text-gray-400 mt-0.5">
                                @unless ($mailbox)<span class="text-gray-500 dark:text-gray-400">{{ $message->mailbox }}@ ·</span>@endunless
                                {{ $message->preview() }}
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-gray-400">
                        {{ $search || $unreadOnly ? 'No messages match.' : 'No mail yet.' }}
                    </p>
                @endforelse
            </div>

            {{ $messages->links() }}
        </div>
    </div>
</x-app-layout>
