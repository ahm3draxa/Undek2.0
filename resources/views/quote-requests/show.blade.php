<x-app-layout>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-space font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            <x-icon name="document-text" class="w-5 h-5" style="color:#7c5cff" />
            {{ $quoteRequest->title }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['ceo', 'admin']);
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-300 dark:border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4 transition-colors duration-200">
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
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Reference images</div>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($quoteRequest->attachments as $attachment)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($attachment->path) }}" target="_blank" rel="noopener" class="block w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:ring-2 hover:ring-cyan-400 transition">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attachment->path) }}" alt="{{ $attachment->original_name }}" class="w-full h-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($isManager && ! $quoteRequest->assignedSpecialist)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Assign a specialist</h3>
                    <p class="text-xs text-gray-400 mb-3">
                        @if ($skillMatched)
                            Showing specialists whose skills match this service.
                        @else
                            No skill match found for this service — showing everyone with a specialist-level role.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('quote-requests.assign', $quoteRequest) }}" class="flex gap-3">
                        @csrf
                        <select name="assigned_to" required class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                            @foreach ($candidateSpecialists as $specialist)
                                <option value="{{ $specialist->id }}">{{ $specialist->name }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>Assign</x-primary-button>
                    </form>
                </div>
            @endif

            @if ($quoteRequest->quotation)
                @php $quotation = $quoteRequest->quotation; @endphp
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-3 transition-colors duration-200">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Quotation</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-300 grid grid-cols-2 gap-2">
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Budget:</span> {{ $quotation->currency }} {{ number_format($quotation->budget, 2) }}</div>
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Estimated:</span> {{ $quotation->estimated_days }} days</div>
                        <div><span class="font-medium text-gray-700 dark:text-gray-300">Status:</span> {{ ucfirst($quotation->status) }}</div>
                    </div>
                    @if ($quotation->notes)
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $quotation->notes }}</p>
                    @endif

                    @if ($quotation->status === 'sent' && ($user->id === $quoteRequest->client_id || $isManager))
                        <form method="POST" action="{{ route('quotations.respond', $quotation) }}" class="flex gap-3 pt-2">
                            @csrf
                            <button name="decision" value="accepted" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-emerald-500">Accept</button>
                            <button name="decision" value="rejected" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-red-500">Decline</button>
                        </form>
                    @endif

                    @if ($quotation->status === 'accepted' && $isManager && ! $quotation->project)
                        <form method="POST" action="{{ route('projects.finalize', $quotation) }}" class="pt-2 space-y-4"
                              x-data="{ milestones: [{ title: '', due_date: '' }] }">
                            @csrf
                            <div>
                                <x-input-label for="lead_expert_id" value="Lead expert (optional — can be set later)" />
                                <p class="text-xs text-gray-400 mb-1">
                                    @if ($expertSkillMatched)
                                        Showing experts whose skills match this service.
                                    @else
                                        No skill match found — showing everyone with an expert/employee role.
                                    @endif
                                </p>
                                <select id="lead_expert_id" name="lead_expert_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Assign later</option>
                                    @foreach ($candidateExperts as $expert)
                                        <option value="{{ $expert->id }}">{{ $expert->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label value="Project due date (optional)" for="due_date" />
                                <x-text-input id="due_date" name="due_date" type="date" class="block mt-1 w-full" />
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
                        <x-action-link href="{{ route('projects.show', $quotation->project) }}" icon="briefcase" class="text-sm">View project</x-action-link>
                    @endif
                </div>
            @elseif ($quoteRequest->assignedSpecialist && ($user->id === $quoteRequest->assigned_to || $isManager))
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 transition-colors duration-200">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Send a quotation</h3>
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
                </div>
            @endif

            <x-chat :index-url="route('quote-requests.messages.index', $quoteRequest)"
                :store-url="route('quote-requests.messages.store', $quoteRequest)"
                title="Discussion" />
        </div>
    </div>
</x-app-layout>
