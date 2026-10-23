<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="document-text" :title="$quoteRequest->title" :subtitle="ucfirst($quoteRequest->status)" />
    </x-slot>

    @php
        $user = auth()->user();
        $isManager = $user->can('manage all work');
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-status-banner />

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 transition-colors duration-200 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px]" style="background:linear-gradient(135deg,#7c5cff,#22d3ee)"></div>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $quoteRequest->service->name }}</div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $quoteRequest->title }}</h3>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        {{ ucfirst($quoteRequest->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $quoteRequest->description }}</p>
                <div class="text-sm text-gray-500 dark:text-gray-400 grid grid-cols-2 gap-2">
                    <div><span class="font-medium text-gray-700 dark:text-gray-300">Requested by:</span> {{ $quoteRequest->client->name ?? $quoteRequest->guest_name }}</div>
                    <div><span class="font-medium text-gray-700 dark:text-gray-300">Contact:</span> {{ $quoteRequest->client->email ?? $quoteRequest->guest_email }}</div>
                    <div><span class="font-medium text-gray-700 dark:text-gray-300">Assigned to:</span> {{ $quoteRequest->assignedSpecialist->name ?? 'Unassigned' }}</div>
                </div>

                @if ($quoteRequest->skills->isNotEmpty())
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Requested skills</div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($quoteRequest->skills as $skill)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($quoteRequest->attachments->isNotEmpty())
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Samples &amp; references</div>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($quoteRequest->attachments as $attachment)
                                @if ($attachment->kind() === 'image')
                                    <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" title="{{ $attachment->original_name }}" class="block w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:ring-2 hover:ring-cyan-400 transition">
                                        <img src="{{ $attachment->url() }}" alt="{{ $attachment->original_name }}" class="w-full h-full object-cover" loading="lazy">
                                    </a>
                                @elseif ($attachment->kind() === 'video')
                                    <video src="{{ $attachment->url() }}" controls preload="metadata" title="{{ $attachment->original_name }}" class="h-40 max-w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-black"></video>
                                @else
                                    <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" class="flex items-center gap-2 h-20 px-4 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:ring-2 hover:ring-cyan-400 transition">
                                        <x-icon name="document-text" class="w-5 h-5 text-gray-400 shrink-0" />
                                        <span class="max-w-[12rem] truncate">{{ $attachment->original_name }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($isManager && ! $quoteRequest->assignedSpecialist)
                @php
                    $specialistSubtitle = match (true) {
                        (bool) $specialistEscalatedTo => 'Nobody in Sales is available — showing '.($specialistEscalatedTo === 'owner' ? 'owners' : 'admins').' instead.',
                        $skillMatched => 'Showing Sales staff whose skills match this service.',
                        default => 'No skill match found for this service — showing everyone in Sales.',
                    };
                @endphp
                <x-panel icon="user-circle" title="Assign a specialist" accent="violet" :subtitle="$specialistSubtitle">
                    @if ($specialistEscalatedTo)
                        <div class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 -mt-2 mb-1">
                            <x-icon name="bolt" class="w-3.5 h-3.5" />
                            No one currently holds the specialist role — assign one on the <a href="{{ route('employees.index') }}" class="underline">Employees</a> page to stop seeing this.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('quote-requests.assign', $quoteRequest) }}" class="flex gap-3">
                        @csrf
                        <select name="assigned_to" required class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach ($candidateSpecialists as $specialist)
                                <option value="{{ $specialist->id }}">{{ $specialist->name }} — {{ $specialist->email }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>Assign</x-primary-button>
                    </form>
                </x-panel>
            @endif

            @php
                $quotations = $quoteRequest->quotations;
                $latestQuotation = $quotations->last();

                // A settled deal (accepted, or already turned into a project) shouldn't
                // still show a "propose new terms" form — that's how an accidental click
                // after acceptance was silently undoing it before finalize could happen.
                $dealSettled = $latestQuotation && in_array($latestQuotation->status, ['accepted'], true);
                $dealFinalized = $latestQuotation && $latestQuotation->project;

                // Each side can only have one proposal outstanding at a time — if the
                // latest is still pending and it's THEIRS, they don't get the form again
                // (no piling up duplicate offers of their own). They can still counter
                // the OTHER side's pending offer without formally declining it first.
                $clientHasOwnPending = $latestQuotation && $latestQuotation->status === 'sent' && $latestQuotation->wasProposedByClient();
                $specialistHasOwnPending = $latestQuotation && $latestQuotation->status === 'sent' && ! $latestQuotation->wasProposedByClient();

                $clientCanPropose = ! $dealFinalized && ! $dealSettled && ! $clientHasOwnPending;
                $specialistCanPropose = ! $dealFinalized && ! $dealSettled && ! $specialistHasOwnPending;

                $statusBadgeClasses = fn ($status) => match ($status) {
                    'sent' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                    'accepted' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'superseded' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    default => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                };
            @endphp

            @if ($quotations->isNotEmpty())
                <x-panel icon="currency-dollar" title="Negotiation" accent="green" subtitle="Every proposal exchanged on this request, oldest first.">
                    <div class="space-y-3">
                        @foreach ($quotations as $quotation)
                            @php
                                $proposedByClient = $quotation->wasProposedByClient();
                                $isCurrent = $quotation->is($latestQuotation);
                                $canRespond = $isCurrent && $quotation->status === 'sent' && ($isManager || ($proposedByClient
                                    ? $user->id === $quoteRequest->assigned_to
                                    : $user->id === $quoteRequest->client_id));
                            @endphp
                            <div class="rounded-lg border {{ $isCurrent ? 'border-cyan-300 dark:border-cyan-700' : 'border-gray-200 dark:border-gray-700' }} p-4">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div>
                                        <div class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                            {{ $proposedByClient ? 'Proposed by client' : 'Proposed by specialist' }}
                                            &middot; {{ $quotation->proposedBy->name ?? ($quotation->specialist->name ?? 'Unknown') }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1 grid grid-cols-2 gap-x-6 gap-y-1">
                                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Amount:</span> {{ $quotation->currency }} {{ number_format($quotation->budget, 2) }}</div>
                                            <div><span class="font-medium text-gray-700 dark:text-gray-300">Delivery:</span> {{ $quotation->delivery_date?->format('M j, Y') ?? $quotation->estimated_days.' days' }}</div>
                                        </div>
                                        @if ($quotation->notes)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $quotation->notes }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClasses($quotation->status) }}">
                                        {{ ucfirst($quotation->status) }}
                                    </span>
                                </div>

                                @if ($canRespond)
                                    <form method="POST" action="{{ route('quotations.respond', $quotation) }}" class="flex gap-3 pt-3">
                                        @csrf
                                        <button name="decision" value="accepted" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-emerald-500">Accept</button>
                                        <button name="decision" value="rejected" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-red-500">Decline</button>
                                    </form>
                                @endif

                                @if ($isCurrent && $quotation->status === 'accepted' && $isManager && ! $quotation->project)
                                    <form method="POST" action="{{ route('projects.finalize', $quotation) }}" class="pt-3 space-y-4"
                                          x-data="{ milestones: [{ title: '', due_date: '' }] }">
                                        @csrf
                                        <div>
                                            <x-input-label for="lead_expert_id" value="Project lead (optional — can be set later)" />
                                            <p class="text-xs text-gray-400 mb-1">
                                                @if ($expertEscalatedTo)
                                                    Nobody who can lead projects is available — showing {{ $expertEscalatedTo === 'owner' ? 'owners' : 'admins' }} instead.
                                                @elseif ($expertSkillMatched)
                                                    Showing people who can lead projects and have a matching skill (the service's own team first).
                                                @else
                                                    No skill match found — showing everyone who can lead projects.
                                                @endif
                                            </p>
                                            <select id="lead_expert_id" name="lead_expert_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                                <option value="">Assign later</option>
                                                @foreach ($candidateExperts as $expert)
                                                    <option value="{{ $expert->id }}">{{ $expert->name }} — {{ $expert->email }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <x-input-label value="Project due date (optional)" for="due_date" />
                                            <x-text-input id="due_date" name="due_date" type="date" class="block mt-1 w-full" value="{{ $quotation->delivery_date?->toDateString() }}" />
                                        </div>

                                        <div>
                                            <x-input-label value="Starting milestones (optional)" />
                                            <p class="text-xs text-gray-400 mb-2">Set up the first checkpoints now — you can always add more later.</p>

                                            <div class="space-y-2">
                                                <template x-for="(milestone, index) in milestones" :key="index">
                                                    <div class="flex gap-2">
                                                        <input type="text" :name="`milestones[${index}][title]`" x-model="milestone.title"
                                                            placeholder="Milestone title" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                        <input type="date" :name="`milestones[${index}][due_date]`" x-model="milestone.due_date"
                                                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                        <button type="button" @click="milestones.splice(index, 1)" x-show="milestones.length > 1"
                                                            class="text-red-400 hover:text-red-500 px-2" aria-label="Remove milestone">&times;</button>
                                                    </div>
                                                </template>
                                            </div>

                                            <button type="button" @click="milestones.push({ title: '', due_date: '' })"
                                                class="mt-2 inline-flex items-center gap-1 text-xs text-cyan-600 dark:text-cyan-400 hover:underline">
                                                <x-icon name="plus" class="w-3.5 h-3.5" />Add another milestone
                                            </button>
                                        </div>

                                        <x-primary-button>Finalize project &rarr;</x-primary-button>
                                    </form>
                                @endif

                                @if ($quotation->project)
                                    <div class="pt-3">
                                        <x-action-link href="{{ route('projects.show', $quotation->project) }}" icon="briefcase" class="text-sm">View project</x-action-link>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-panel>
            @endif

            @if ($quoteRequest->assignedSpecialist)
                @if ($specialistCanPropose && ($user->id === $quoteRequest->assigned_to || $isManager))
                    <x-panel icon="currency-dollar" :title="$quotations->isEmpty() ? 'Send a quotation' : 'Send revised terms'"
                        accent="amber" :subtitle="$quotations->isEmpty() ? null : 'Sending this replaces whatever proposal is currently on the table.'">
                        <form method="POST" action="{{ route('quotations.store', $quoteRequest) }}" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="budget" value="Budget (USD)" />
                                    <x-text-input id="budget" name="budget" type="number" step="0.01" min="0" class="block mt-1 w-full" required />
                                    <x-input-error :messages="$errors->get('budget')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="estimated_days" value="Estimated days" />
                                    <x-text-input id="estimated_days" name="estimated_days" type="number" min="1" class="block mt-1 w-full" required />
                                    <x-input-error :messages="$errors->get('estimated_days')" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="notes" value="Notes (optional)" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                            </div>
                            <x-primary-button>Send quotation</x-primary-button>
                        </form>
                    </x-panel>
                @endif

                @if ($clientCanPropose && $user->id === $quoteRequest->client_id)
                    <x-panel icon="sparkles" title="Propose your own terms" accent="violet"
                        :subtitle="$quotations->isEmpty() ? 'Suggest your own budget and delivery date.' : 'Sending this replaces whatever proposal is currently on the table.'">
                        <form method="POST" action="{{ route('quotations.counter', $quoteRequest) }}" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="counter_budget" value="Your budget (USD)" />
                                    <x-text-input id="counter_budget" name="budget" type="number" step="0.01" min="0" class="block mt-1 w-full" required />
                                    <x-input-error :messages="$errors->get('budget')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="counter_delivery_date" value="Delivery date you need" />
                                    <x-text-input id="counter_delivery_date" name="delivery_date" type="date" class="block mt-1 w-full" required />
                                    <x-input-error :messages="$errors->get('delivery_date')" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="counter_notes" value="Notes (optional)" />
                                <textarea id="counter_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm"></textarea>
                            </div>
                            <x-primary-button>Propose these terms</x-primary-button>
                        </form>
                    </x-panel>
                @endif
            @endif

            <x-chat :index-url="route('quote-requests.messages.index', $quoteRequest)"
                :store-url="route('quote-requests.messages.store', $quoteRequest)"
                title="Discussion" />
        </div>
    </div>
</x-app-layout>
