@props(['indexUrl', 'storeUrl', 'title' => 'Chat'])

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200"
    x-data="{
        messages: [],
        lastId: 0,
        draft: '',
        sending: false,
        error: false,
        async refresh() {
            try {
                const url = new URL('{{ $indexUrl }}', window.location.origin);
                if (this.lastId) url.searchParams.set('after', this.lastId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('bad response');
                const data = await res.json();
                if (data.messages.length) {
                    this.messages.push(...data.messages);
                    this.lastId = this.messages[this.messages.length - 1].id;
                    this.$nextTick(() => this.scrollToBottom());
                }
                this.error = false;
            } catch (e) {
                this.error = true;
            }
        },
        async send() {
            const body = this.draft.trim();
            if (!body || this.sending) return;
            this.sending = true;
            try {
                const res = await fetch('{{ $storeUrl }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ body }),
                });
                if (!res.ok) throw new Error('bad response');
                const data = await res.json();
                this.messages.push(data.message);
                this.lastId = data.message.id;
                this.draft = '';
                this.$nextTick(() => this.scrollToBottom());
            } catch (e) {
                this.error = true;
            } finally {
                this.sending = false;
            }
        },
        scrollToBottom() {
            const el = this.$refs.scroller;
            if (el) el.scrollTop = el.scrollHeight;
        },
        init() {
            this.refresh().then(() => this.scrollToBottom());
            setInterval(() => this.refresh(), 4000);
        },
    }"
>
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <x-icon name="chat-bubble-left-right" class="w-4 h-4" style="color:#7c5cff" />
            {{ $title }}
        </h3>
        <p x-show="error" x-cloak class="text-xs text-red-500 dark:text-red-400">Connection issue — retrying…</p>
    </div>

    <div x-ref="scroller" class="h-72 overflow-y-auto rounded-md bg-gray-50 dark:bg-gray-900/50 p-3 space-y-2 mb-3">
        <template x-if="messages.length === 0">
            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">No messages yet — say hello.</p>
        </template>
        <template x-for="message in messages" :key="message.id">
            <div class="flex" :class="message.is_mine ? 'justify-end' : 'justify-start'">
                <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm"
                    :class="message.is_mine ? 'bg-cyan-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600'">
                    <div class="text-[10px] uppercase tracking-wider opacity-70 mb-0.5" x-text="message.is_mine ? 'You' : message.user_name"></div>
                    <div class="whitespace-pre-line" x-text="message.body"></div>
                    <div class="text-[10px] opacity-60 mt-1 text-right" x-text="message.created_at_human"></div>
                </div>
            </div>
        </template>
    </div>

    <form @submit.prevent="send" class="flex gap-2">
        <input type="text" x-model="draft" placeholder="Type a message…" :disabled="sending"
            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm">
        <button type="submit" :disabled="sending || !draft.trim()"
            class="inline-flex items-center px-4 py-2 bg-cyan-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-cyan-500 disabled:opacity-50">
            Send
        </button>
    </form>
</div>
