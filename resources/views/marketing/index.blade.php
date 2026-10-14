<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="sparkles" title="Marketing" subtitle="Send offers and updates to clients, and track what's gone out." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card icon="document-text" label="Campaigns sent" :value="$stats['campaigns']" accent="violet" />
                <x-stat-card icon="users" label="Recipients reached" :value="$stats['recipients']" accent="green" />
                <x-stat-card icon="user-circle" label="Total clients" :value="$stats['clients']" accent="pink" />
                <x-stat-card icon="x-circle" label="Unsubscribed" :value="$stats['unsubscribed']" accent="amber" />
            </div>

            <x-panel icon="sparkles" title="Send a campaign" accent="amber" subtitle="Sent from Undek's mailbox under your name — replies come straight to you. Unsubscribed addresses are skipped automatically."
                x-data="{
                    audience: '{{ old('audience', 'all_clients') }}',
                    emails: @js(old('emails', '')),
                    get emailCount() {
                        return new Set(this.emails.split(/[\s,;]+/).map(e => e.trim().toLowerCase()).filter(Boolean)).size;
                    },
                }">

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
                            <label class="flex items-center gap-2">
                                <input type="radio" name="audience" value="manual" x-model="audience" style="accent-color:#7c5cff">
                                Paste a list
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

                    <div x-show="audience === 'manual'" x-cloak>
                        <x-input-label for="emails" value="Email addresses — one per line" />
                        <textarea id="emails" name="emails" rows="8" x-model="emails" :required="audience === 'manual'"
                            placeholder="ali@company.com&#10;sara@example.pk&#10;…"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm font-mono text-sm"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span x-text="emailCount"></span> unique address(es) · up to {{ $maxPerSend }} per campaign.
                            Pasting from a spreadsheet works too — commas and semicolons are fine. Only send to people who expect to hear from Undek.
                        </p>
                        <x-input-error :messages="$errors->get('emails')" class="mt-2" />
                    </div>

                    <x-primary-button>Send campaign</x-primary-button>
                </form>
            </x-panel>

            <x-panel icon="clock" title="Campaign history" accent="violet">
                <div class="space-y-2">
                    @forelse ($campaigns as $campaign)
                        <div class="border border-gray-100 dark:border-gray-700 rounded-md px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $campaign->subject }}</p>
                                <span class="text-xs text-gray-400">{{ $campaign->sent_at?->format('M j, Y g:i A') }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Sent by {{ $campaign->sender->name ?? 'Unknown' }} to {{ $campaign->recipients_count }} recipient(s)
                                &middot; {{ ['all_clients' => 'All clients', 'selected' => 'Selected clients', 'manual' => 'Pasted list'][$campaign->audience] ?? $campaign->audience }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No campaigns sent yet.</p>
                    @endforelse
                </div>

                {{ $campaigns->links() }}
            </x-panel>
        </div>
    </div>
</x-app-layout>
