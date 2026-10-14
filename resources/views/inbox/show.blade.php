<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="envelope" :title="$message->subject ?: '(no subject)'" :subtitle="'To '.$message->mailboxAddress()" />
    </x-slot>

    <div class="py-12" x-data="{ images: false }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <a href="{{ route('inbox.index', ['mailbox' => $message->mailbox]) }}" class="text-cyan-600 dark:text-cyan-400 hover:underline">&larr; Back to inbox</a>
                @if ($message->direction === 'in')
                    <form method="POST" action="{{ route('inbox.unread', $message) }}">
                        @csrf
                        <button class="text-gray-500 dark:text-gray-400 hover:underline">Mark as unread</button>
                    </form>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 text-sm">
                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $message->from_name ?: $message->from_email }}
                            @if ($message->from_name)
                                <span class="font-normal text-gray-500 dark:text-gray-400">&lt;{{ $message->from_email }}&gt;</span>
                            @endif
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 truncate">To: {{ $message->to ?: $message->mailboxAddress() }}</p>
                        @if ($message->cc)
                            <p class="text-gray-500 dark:text-gray-400 truncate">Cc: {{ $message->cc }}</p>
                        @endif
                    </div>
                    <div class="text-right text-xs text-gray-400">
                        <p>{{ $message->received_at?->format('D, M j, Y g:i A') }}</p>
                        @if ($message->reader)
                            <p class="mt-0.5">Opened by {{ $message->reader->name }}</p>
                        @endif
                    </div>
                </div>

                @if ($message->body_html)
                    <div class="px-6 py-2 bg-gray-50 dark:bg-gray-900/40 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between gap-3" x-show="!images">
                        <span>Remote images are hidden to protect your privacy.</span>
                        <button type="button" class="text-cyan-600 dark:text-cyan-400 hover:underline" @click="images = true">Show images</button>
                    </div>
                @endif

                {{-- Untrusted email HTML: isolated in a sandboxed frame, see InboxController::body(). --}}
                <iframe sandbox="allow-popups allow-popups-to-escape-sandbox"
                    :src="images ? '{{ route('inbox.body', [$message, 'images' => 1]) }}' : '{{ route('inbox.body', $message) }}'"
                    src="{{ route('inbox.body', $message) }}"
                    title="Email content" referrerpolicy="no-referrer"
                    class="w-full bg-white" style="height:60vh;border:0"></iframe>

                @if ($message->attachments->isNotEmpty())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                        @foreach ($message->attachments as $attachment)
                            @if ($attachment->isStored())
                                <a href="{{ route('inbox.attachments.show', [$message, $attachment]) }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:ring-1 hover:ring-cyan-400">
                                    <x-icon name="paper-clip" class="w-3.5 h-3.5 text-gray-400" />
                                    <span class="max-w-[16rem] truncate">{{ $attachment->original_name }}</span>
                                    <span class="text-gray-400">{{ app(\App\Services\Uploads\SecureUploader::class)->humanSize($attachment->size) }}</span>
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-red-50 dark:bg-red-500/10 text-xs text-red-700 dark:text-red-300"
                                    title="{{ $attachment->mime_type === 'blocked' ? 'This file type can carry malware, so it was not downloaded. Open it in webmail only if you trust the sender.' : 'Too large to keep in the portal — open it in webmail.' }}">
                                    <x-icon name="x-circle" class="w-3.5 h-3.5" />
                                    <span class="max-w-[16rem] truncate">{{ $attachment->original_name }}</span>
                                    <span>{{ $attachment->mime_type === 'blocked' ? 'blocked' : 'too large' }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($message->replies as $reply)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-6 py-4 border-l-4 border-cyan-400">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <x-icon name="arrow-uturn-left" class="inline w-3.5 h-3.5 -mt-0.5" />
                        {{ $reply->sender->name ?? 'Someone' }} replied from {{ $reply->from_email }} · {{ $reply->received_at?->format('M j, Y g:i A') }}
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ $reply->body_text }}</p>
                </div>
            @endforeach

            @if ($message->direction === 'in' && $message->from_email)
                <x-panel icon="arrow-uturn-left" title="Reply" accent="violet" :subtitle="'Sent from '.$message->mailboxAddress().' to '.$message->from_email">
                    <form method="POST" action="{{ route('inbox.reply', $message) }}" class="space-y-3">
                        @csrf
                        <textarea name="body" rows="7" required maxlength="20000" placeholder="Write your reply…"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" />
                        <x-primary-button>Send reply</x-primary-button>
                    </form>
                </x-panel>
            @endif
        </div>
    </div>
</x-app-layout>
