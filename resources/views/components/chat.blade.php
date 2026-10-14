@props(['indexUrl', 'storeUrl', 'title' => 'Chat'])

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 transition-colors duration-200 relative overflow-hidden"
    x-data="{
        messages: [],
        lastId: 0,
        draft: '',
        sending: false,
        error: false,
        loaded: false,
        roleRing(color) {
            return {
                violet: 'ring-2 ring-[#7c5cff]/60',
                cyan: 'ring-2 ring-[#22d3ee]/60',
                amber: 'ring-2 ring-[#ffb020]/60',
            }[color] ?? '';
        },
        roleBadge(color) {
            return {
                violet: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
                amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            }[color] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        },
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
                this.loaded = true;
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
            pollWhileVisible(() => this.refresh(), 15000);
        },
    }"
>
    <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>

    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <span class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
                <x-icon name="chat-bubble-left-right" class="w-3.5 h-3.5 text-white" />
            </span>
            {{ $title }}
        </h3>
        <p x-show="error" x-cloak class="text-xs text-red-500 dark:text-red-400">Connection issue — retrying…</p>
    </div>

    <div x-ref="scroller" class="h-96 overflow-y-auto rounded-lg bg-gray-50 dark:bg-gray-900/50 p-4 space-y-4 mb-3">
        <template x-if="!loaded">
            <div class="h-full flex flex-col items-center justify-center gap-2 text-gray-400 dark:text-gray-500 py-8">
                <svg class="w-6 h-6 animate-spin opacity-40" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </template>

        <template x-if="loaded && messages.length === 0">
            <div class="h-full flex flex-col items-center justify-center gap-2 text-gray-400 dark:text-gray-500 py-8">
                <x-icon name="chat-bubble-left-right" class="w-8 h-8 opacity-40" />
                <p class="text-sm">No messages yet — say hello.</p>
            </div>
        </template>

        <template x-for="message in messages" :key="message.id">
            <div>
                <template x-if="message.type === 'event'">
                    <div class="flex justify-center">
                        <span class="text-[11px] text-gray-500 dark:text-gray-400 bg-gray-200/70 dark:bg-gray-700/60 rounded-full px-3 py-1 text-center" x-text="message.body"></span>
                    </div>
                </template>
                <template x-if="message.type !== 'event'">
                    <div class="flex items-end gap-2" :class="message.is_mine ? 'flex-row-reverse' : 'flex-row'">
                        <div class="shrink-0 w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-[10px] font-bold text-white"
                            :class="roleRing(message.user_role_color)"
                            :style="!message.user_avatar ? 'background:linear-gradient(135deg,#7c5cff,#22d3ee)' : ''">
                            <img x-show="message.user_avatar" :src="message.user_avatar" class="w-full h-full object-cover" alt="">
                            <span x-show="!message.user_avatar" x-text="message.user_initials"></span>
                        </div>

                        <div class="max-w-[75%] flex flex-col" :class="message.is_mine ? 'items-end' : 'items-start'">
                            <div class="flex items-center gap-1.5 mb-1" :class="message.is_mine ? 'flex-row-reverse' : 'flex-row'">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200" x-text="message.is_mine ? 'You' : message.user_name"></span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide"
                                    :class="roleBadge(message.user_role_color)" x-text="message.user_role"></span>
                            </div>
                            <div class="rounded-2xl px-3.5 py-2 text-sm shadow-sm"
                                :class="message.is_mine
                                    ? 'text-white rounded-br-sm'
                                    : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-bl-sm'"
                                :style="message.is_mine ? 'background:linear-gradient(135deg,#7c5cff,#22d3ee)' : ''">
                                <div class="whitespace-pre-line" x-text="message.body"></div>
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1" x-text="message.created_at_human"></div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <form @submit.prevent="send" class="flex gap-2">
        <input type="text" x-model="draft" placeholder="Type a message…" :disabled="sending"
            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm">
        <button type="submit" :disabled="sending || !draft.trim()"
            class="inline-flex items-center px-4 py-2 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:opacity-90 disabled:opacity-50 transition"
            style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
            Send
        </button>
    </form>
</div>
