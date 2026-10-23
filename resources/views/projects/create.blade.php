<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="plus" title="New project" subtitle="Set up a project directly — for a phone or WhatsApp client, repeat work, or an internal project." />
    </x-slot>

    @php $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm'; @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-6"
                x-data="{
                    clientMode: '{{ old('client_mode', 'existing') }}',
                    milestones: @js(old('milestones', [['title' => '', 'due_date' => '']])),
                }">
                @csrf

                <x-panel icon="briefcase" title="Project" accent="violet">
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="title" value="Title" />
                            <x-text-input id="title" name="title" type="text" class="block mt-1 w-full" :value="old('title')" required autofocus placeholder="e.g. Company website redesign" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="service_id" value="Service" />
                            <select id="service_id" name="service_id" required class="{{ $fieldClass }}">
                                <option value="">Choose a service…</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                        {{ $service->name }}{{ $service->department ? ' — '.$service->department->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('service_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="Description (optional)" />
                            <textarea id="description" name="description" rows="4" class="{{ $fieldClass }}" placeholder="What's being delivered, any agreed scope…">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </x-panel>

                <x-panel icon="user-circle" title="Client" accent="pink">
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-200">
                            @foreach (['existing' => 'Existing client', 'new' => 'New client', 'internal' => 'Internal (Undek\'s own project)'] as $value => $label)
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="client_mode" value="{{ $value }}" x-model="clientMode" style="accent-color:#7c5cff">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div x-show="clientMode === 'existing'" x-cloak>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" class="{{ $fieldClass }}" :required="clientMode === 'existing'">
                                <option value="">Choose a client…</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }} — {{ $client->email }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        <div x-show="clientMode === 'new'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="new_client_name" value="Client name" />
                                <x-text-input id="new_client_name" name="new_client_name" type="text" class="block mt-1 w-full" :value="old('new_client_name')" x-bind:required="clientMode === 'new'" />
                                <x-input-error :messages="$errors->get('new_client_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="new_client_email" value="Client email" />
                                <x-text-input id="new_client_email" name="new_client_email" type="email" class="block mt-1 w-full" :value="old('new_client_email')" x-bind:required="clientMode === 'new'" />
                                <x-input-error :messages="$errors->get('new_client_email')" class="mt-2" />
                            </div>
                            <p class="sm:col-span-2 text-xs text-gray-500 dark:text-gray-400">They'll get an email with a link to choose a password and follow the project in the portal.</p>
                        </div>

                        <p x-show="clientMode === 'internal'" x-cloak class="text-xs text-gray-500 dark:text-gray-400">No client — only the team will see this project.</p>
                    </div>
                </x-panel>

                <x-panel icon="clock" title="Budget & schedule" accent="amber">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <x-input-label for="budget" value="Budget (optional)" />
                            <x-text-input id="budget" name="budget" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('budget')" />
                            <x-input-error :messages="$errors->get('budget')" class="mt-2" />
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <x-input-label for="currency" value="Currency" />
                            <select id="currency" name="currency" class="{{ $fieldClass }}">
                                @foreach (['USD', 'PKR', 'GBP', 'EUR', 'AED'] as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency', 'USD') === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="start_date" value="Start" />
                            <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full" :value="old('start_date', now()->toDateString())" />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Due (optional)" />
                            <x-text-input id="due_date" name="due_date" type="date" class="block mt-1 w-full" :value="old('due_date')" />
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>
                    </div>
                </x-panel>

                <x-panel icon="users" title="Lead & milestones" accent="violet">
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="lead_expert_id" value="Project lead (optional — can be set later)" />
                            <select id="lead_expert_id" name="lead_expert_id" class="{{ $fieldClass }}">
                                <option value="">Assign later</option>
                                @foreach ($leadCandidates as $lead)
                                    <option value="{{ $lead->id }}" @selected((string) old('lead_expert_id') === (string) $lead->id)>{{ $lead->name }} — {{ $lead->title() }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add team members from the project page once it's created.</p>
                            <x-input-error :messages="$errors->get('lead_expert_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Starting milestones (optional)" />
                            <template x-for="(milestone, index) in milestones" :key="index">
                                <div class="flex gap-2 mt-2">
                                    <input type="text" :name="`milestones[${index}][title]`" x-model="milestone.title" placeholder="Milestone title"
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm">
                                    <input type="date" :name="`milestones[${index}][due_date]`" x-model="milestone.due_date"
                                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm">
                                    <button type="button" @click="milestones.splice(index, 1)" x-show="milestones.length > 1" class="text-gray-400 hover:text-red-500 px-2" aria-label="Remove milestone">&times;</button>
                                </div>
                            </template>
                            <button type="button" @click="milestones.push({ title: '', due_date: '' })" class="mt-2 text-sm text-cyan-600 dark:text-cyan-400 hover:underline">+ Add milestone</button>
                        </div>
                    </div>
                </x-panel>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Cancel</a>
                    <x-primary-button>Create project</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
