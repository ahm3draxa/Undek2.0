<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="sparkles" class="w-5 h-5" style="color:#7c5cff" />
            {{ __('Marketing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200"
                x-data="{ audience: '{{ old('audience', 'all_clients') }}' }">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Send a campaign</h3>
                <p class="text-xs text-gray-400 -mt-2">Emails are delivered through the mail driver configured for this environment.</p>

                <form method="POST" action="{{ route('marketing.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="subject" value="Subject" />
                        <x-text-input id="subject" name="subject" type="text" class="block mt-1 w-full" :value="old('subject')" required />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="body" value="Message" />
                        <textarea id="body" name="body" rows="6" required placeholder="Write your offer, announcement, or update…"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Audience" />
                        <div class="flex gap-4 mt-1 text-sm text-gray-700 dark:text-gray-200">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="audience" value="all_clients" x-model="audience" style="accent-color:#7c5cff">
                                All clients ({{ $clients->count() }})
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="audience" value="selected" x-model="audience" style="accent-color:#7c5cff">
                                Selected clients
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('audience')" class="mt-2" />
                    </div>

                    <div x-show="audience === 'selected'" x-cloak>
                        <div class="max-h-48 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-1.5">
                            @forelse ($clients as $client)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                                        @checked(in_array($client->id, old('client_ids', [])))
                                        class="rounded border-gray-300 dark:border-gray-600" style="accent-color:#7c5cff">
                                    {{ $client->name }} <span class="text-gray-400">({{ $client->email }})</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-400">No clients yet.</p>
                            @endforelse
                        </div>
                        <x-input-error :messages="$errors->get('client_ids')" class="mt-2" />
                    </div>

                    <x-primary-button>Send campaign</x-primary-button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Campaign history</h3>

                <div class="space-y-2">
                    @forelse ($campaigns as $campaign)
                        <div class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $campaign->subject }}</p>
                                <span class="text-xs text-gray-400">{{ $campaign->sent_at?->format('M j, Y g:i A') }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Sent by {{ $campaign->sender->name ?? 'Unknown' }} to {{ $campaign->recipients_count }} recipient(s)
                                &middot; {{ $campaign->audience === 'all_clients' ? 'All clients' : 'Selected clients' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No campaigns sent yet.</p>
                    @endforelse
                </div>

                {{ $campaigns->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
