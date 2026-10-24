@props(['direction' => 'down', 'align' => 'right'])

<div class="relative"
    x-data="{
        open: false,
        loaded: false,
        items: [],
        unread: 0,
        async refresh() {
            try {
                const res = await fetch('{{ route('notifications.index') }}', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('bad response');
                const data = await res.json();
                this.items = data.notifications;
                this.unread = data.unread_count;
                this.loaded = true;
            } catch (e) {}
        },
        async visit(item) {
            this.open = false;
            if (!item.read) {
                await fetch(`/notifications/${item.id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
            }
            window.location = item.url;
        },
        async markAllRead() {
            await fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            });
            this.items.forEach(i => i.read = true);
            this.unread = 0;
        },
        init() {
            this.refresh();
            pollWhileVisible(() => this.refresh(), 120000);
        },
    }"
    @click.outside="open = false"
>
    <button @click="open = !open" type="button"
        class="relative inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out"
        aria-label="Notifications">
        <x-icon name="bell" class="w-5 h-5" />
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
            class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full text-white text-[10px] font-bold flex items-center justify-center"
            style="background:linear-gradient(135deg,#ff5da2,#7c5cff)"></span>
    </button>

    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 {{ $direction === 'up' ? 'bottom-full mb-2' : 'mt-2' }} {{ $align === 'left' ? 'left-0' : 'right-0' }} w-80 max-w-[85vw] rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black dark:ring-white ring-opacity-5 dark:ring-opacity-10 max-h-96 overflow-y-auto">
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notifications</span>
            <button @click="markAllRead" x-show="unread > 0" class="text-[11px] text-cyan-600 dark:text-cyan-400 hover:underline">Mark all read</button>
        </div>

        <template x-if="loaded && items.length === 0">
            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">No notifications yet.</p>
        </template>

        <template x-for="item in items" :key="item.id">
            <button @click="visit(item)" class="w-full text-left px-4 py-3 border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition"
                :class="!item.read ? 'bg-cyan-50 dark:bg-cyan-900/10' : ''">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="!item.read ? 'bg-cyan-500' : 'bg-transparent'"></span>
                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="item.sender_name"></span>
                    <span class="text-[10px] text-gray-400 shrink-0" x-text="'· ' + item.thread_label"></span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5 truncate" x-text="item.body"></p>
                <p class="text-[10px] text-gray-400 mt-1" x-text="item.created_at_human"></p>
            </button>
        </template>
    </div>
</div>
