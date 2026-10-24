@props(['indexUrl', 'storeUrl', 'title' => 'Chat', 'subtitle' => null, 'framed' => true])

{{--
    Polling chat. Refreshes every 10 s while a conversation is active (a message
    in the last 2 minutes, or the person is typing) and once a minute otherwise;
    never while the tab is hidden, the person is idle, or this chat isn't on
    screen (e.g. an inactive tab) — every refresh costs one of the site's 500
    hourly MySQL connections.
--}}
<div {{ $attributes->class([
        'relative flex flex-col transition-colors duration-200',
        'bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden' => $framed,
    ]) }}
    x-data="{
        messages: [],
        lastId: 0,
        draft: '',
        sending: false,
        error: false,
        loaded: false,
        atBottom: true,
        unseen: 0,
        lastActivity: 0,
        now: Date.now(),
        poller: null,
        maxLength: 5000,
        get fast() { return this.now - this.lastActivity < 120000; },
        roleBadge(color) {
            return {
                violet: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
                amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            }[color] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        },
        // Consecutive messages from the same person within 5 minutes read as one block.
        startsGroup(index) {
            const message = this.messages[index], previous = this.messages[index - 1];
            if (!previous || previous.type === 'event' || message.type === 'event') return true;
            return previous.user_id !== message.user_id
                || new Date(message.created_at) - new Date(previous.created_at) > 5 * 60 * 1000
                || this.startsDay(index);
        },
        endsGroup(index) {
            return index === this.messages.length - 1 || this.startsGroup(index + 1);
        },
        startsDay(index) {
            const previous = this.messages[index - 1];
            return !previous || new Date(previous.created_at).toDateString() !== new Date(this.messages[index].created_at).toDateString();
        },
        isVisible() { return this.$el.offsetParent !== null; },
        async refresh(force = false) {
            if (!force && this.loaded && !this.isVisible()) return;
            try {
                const url = new URL('{{ $indexUrl }}', window.location.origin);
                if (this.lastId) url.searchParams.set('after', this.lastId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('bad response');
                const data = await res.json();
                if (data.messages.length) {
                    const wasAtBottom = this.atBottom;
                    this.messages.push(...data.messages);
                    this.lastId = this.messages[this.messages.length - 1].id;
                    if (this.loaded) {
                        this.lastActivity = this.now = Date.now();
                        if (!wasAtBottom) this.unseen += data.messages.filter(m => !m.is_mine).length;
                    }
                    if (wasAtBottom || !this.loaded) this.$nextTick(() => this.scrollToBottom());
                }
                this.error = false;
                this.loaded = true;
            } catch (e) {
                this.error = true;
            }
        },
        async send() {
            const body = this.draft.trim();
            if (!body || this.sending || body.length > this.maxLength) return;
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
                this.lastActivity = this.now = Date.now();
                this.draft = '';
                this.$nextTick(() => { this.resize(); this.scrollToBottom(); });
            } catch (e) {
                this.error = true;
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },
        onKeydown(event) {
            this.lastActivity = this.now = Date.now();
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                this.send();
            }
        },
        resize() {
            const input = this.$refs.input;
            if (!input) return;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 160) + 'px';
        },
        onScroll() {
            const el = this.$refs.scroller;
            this.atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 40;
            if (this.atBottom) this.unseen = 0;
        },
        scrollToBottom() {
            const el = this.$refs.scroller;
            if (el) el.scrollTop = el.scrollHeight;
            this.atBottom = true;
            this.unseen = 0;
        },
        init() {
            this.refresh(true).then(() => this.scrollToBottom());
            // A local clock tick (no network) so 'Live' fades to the slow pace on time.
            setInterval(() => this.now = Date.now(), 15000);
            this.poller = pollWhileVisible(() => this.refresh(), () => (Date.now() - this.lastActivity < 120000) ? 10000 : 60000);
            // Coming back to a chat (switching tabs) shows the latest straight away.
            new IntersectionObserver(([entry]) => { if (entry.isIntersecting && this.loaded) this.refresh(true); }).observe(this.$el);
        },
    }"
>
    @if ($framed)
        <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
        <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
                    <x-icon name="chat-bubble-left-right" class="w-4 h-4 text-white" />
                </span>
                <div class="min-w-0">
                    <h3 class="font-space font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <x-chat-status />
        </div>
    @else
        <div class="flex justify-end px-5 pt-3">
            <x-chat-status />
        </div>
    @endif

    <div class="relative">
        <div x-ref="scroller" @scroll.passive="onScroll" class="h-[26rem] overflow-y-auto bg-gray-50 dark:bg-gray-900/40 px-4 py-3 mx-5 rounded-xl">
            <template x-if="!loaded">
                <div class="h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                    <svg class="w-6 h-6 animate-spin opacity-40" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </div>
            </template>

            <template x-if="loaded && messages.length === 0">
                <div class="h-full flex flex-col items-center justify-center gap-2 text-center text-gray-400 dark:text-gray-500">
                    <x-icon name="chat-bubble-left-right" class="w-9 h-9 opacity-40" />
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No messages yet</p>
                    <p class="text-xs">Start the conversation — everyone on this thread is notified.</p>
                </div>
            </template>

            <template x-for="(message, index) in messages" :key="message.id">
                <div>
                    {{-- Day separator --}}
                    <template x-if="startsDay(index)">
                        <div class="flex items-center gap-3 my-3">
                            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                            <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500" x-text="chatFormat.day(message.created_at)"></span>
                            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </template>

                    {{-- System event --}}
                    <template x-if="message.type === 'event'">
                        <div class="flex justify-center my-2">
                            <span class="max-w-[90%] text-[11px] text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full px-3 py-1 text-center"
                                :title="chatFormat.full(message.created_at)">
                                <span x-text="message.body"></span>
                                <span class="text-gray-400 dark:text-gray-500">· <span x-text="chatFormat.time(message.created_at)"></span></span>
                            </span>
                        </div>
                    </template>

                    {{-- Message --}}
                    <template x-if="message.type !== 'event'">
                        <div class="flex items-end gap-2" :class="[message.is_mine ? 'flex-row-reverse' : 'flex-row', startsGroup(index) ? 'mt-3' : 'mt-0.5']">
                            <div class="shrink-0 w-8">
                                <template x-if="endsGroup(index) && !message.is_mine">
                                    <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-[10px] font-bold text-white"
                                        :style="!message.user_avatar ? 'background:linear-gradient(135deg,#7c5cff,#22d3ee)' : ''">
                                        <img x-show="message.user_avatar" :src="message.user_avatar" class="w-full h-full object-cover" alt="">
                                        <span x-show="!message.user_avatar" x-text="message.user_initials"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="max-w-[78%] flex flex-col" :class="message.is_mine ? 'items-end' : 'items-start'">
                                <template x-if="startsGroup(index) && !message.is_mine">
                                    <div class="flex items-center gap-1.5 mb-1 px-1">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200" x-text="message.user_name"></span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide"
                                            :class="roleBadge(message.user_role_color)" x-text="message.user_role"></span>
                                    </div>
                                </template>
                                <div class="px-3.5 py-2 text-sm shadow-sm rounded-2xl"
                                    :title="chatFormat.full(message.created_at)"
                                    :class="[
                                        message.is_mine
                                            ? 'text-white'
                                            : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600',
                                        endsGroup(index) ? (message.is_mine ? 'rounded-br-md' : 'rounded-bl-md') : '',
                                    ]"
                                    :style="message.is_mine ? 'background:linear-gradient(135deg,#7c5cff,#22d3ee)' : ''">
                                    <div class="whitespace-pre-line break-words" x-html="chatFormat.linkify(message.body)"></div>
                                </div>
                                <template x-if="endsGroup(index)">
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 px-1" x-text="chatFormat.time(message.created_at)"></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <button type="button" x-show="!atBottom && unseen > 0" x-cloak @click="scrollToBottom()"
            class="absolute bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white shadow-lg"
            style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
            <span x-text="unseen === 1 ? '1 new message' : unseen + ' new messages'"></span> &darr;
        </button>
    </div>

    <form @submit.prevent="send" class="flex items-end gap-2 p-5 pt-3">
        <div class="flex-1">
            <textarea x-ref="input" x-model="draft" rows="1" @input="resize()" @keydown="onKeydown"
                :maxlength="maxLength" placeholder="Write a message…" aria-label="Message"
                class="block w-full resize-none rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm text-sm py-2.5 leading-5"></textarea>
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500 flex justify-between gap-2">
                <span><kbd class="font-sans">Enter</kbd> to send · <kbd class="font-sans">Shift</kbd>+<kbd class="font-sans">Enter</kbd> for a new line</span>
                <span x-show="draft.length > maxLength - 500" x-cloak :class="draft.length >= maxLength ? 'text-red-500' : ''" x-text="draft.length + ' / ' + maxLength"></span>
            </p>
        </div>
        <button type="submit" :disabled="sending || !draft.trim()" aria-label="Send message"
            class="mb-6 inline-flex items-center justify-center w-10 h-10 rounded-xl text-white shadow-sm hover:opacity-90 disabled:opacity-40 transition shrink-0"
            style="background:linear-gradient(135deg,#7c5cff,#22d3ee)">
            <svg x-show="!sending" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
        </button>
    </form>
</div>
