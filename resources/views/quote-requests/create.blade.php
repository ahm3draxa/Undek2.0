<x-guest-layout :title="__('Request a quote')" description="Tell Undek about your web, SEO, IT support, design or video project and get a tailored proposal from a specialist.">
    <div class="mb-6">
        <h1 class="font-space text-2xl font-bold text-[#f5f5fa]">Tell us about your project</h1>
        <p class="text-sm text-gray-400 mt-1.5">Pick a service and give us the details — a specialist will send a tailored quote.</p>
    </div>

    @if (session('status'))
        <x-auth.status class="mb-5" :status="session('status')" />
    @endif

    <form method="POST" action="{{ route('quote-requests.store') }}" class="space-y-5" enctype="multipart/form-data"
        x-data="{
            skillsByService: @js($skillsByService),
            serviceId: '{{ old('service_id', $selectedServiceId ?? '') }}',
            checked: @js(array_map('strval', old('skills', []))),
            get categories() {
                return Object.entries(this.skillsByService[this.serviceId] || {});
            },
            toggle(id) {
                id = String(id);
                const i = this.checked.indexOf(id);
                if (i === -1) { this.checked.push(id); } else { this.checked.splice(i, 1); }
            },
        }"
    >
        @csrf

        <div>
            <x-auth.label for="service_id" :value="__('Service')" />
            <select id="service_id" name="service_id" required x-model="serviceId"
                class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-sm text-gray-100 outline-none transition focus:border-cyan-400/60 focus:ring-2 focus:ring-cyan-400/20">
                <option value="" class="bg-[#0c0c12]">Select a service…</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" class="bg-[#0c0c12]" @selected(old('service_id', $selectedServiceId ?? null) == $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
            <x-auth.error :messages="$errors->get('service_id')" />
        </div>

        <div>
            <x-auth.label for="title" :value="__('Project title')" />
            <x-auth.input id="title" type="text" name="title" :value="old('title')" required placeholder="e.g. Redesign our company website" />
            <x-auth.error :messages="$errors->get('title')" />
        </div>

        <div>
            <x-auth.label for="description" :value="__('Project details')" />
            <textarea id="description" name="description" rows="5" required placeholder="What are you trying to build or fix?"
                class="block w-full rounded-lg bg-white/5 border border-white/10 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 outline-none transition focus:border-cyan-400/60 focus:ring-2 focus:ring-cyan-400/20">{{ old('description') }}</textarea>
            <x-auth.error :messages="$errors->get('description')" />
        </div>

        <div>
            <x-auth.label value="Relevant skills (optional)" />
            <p class="text-xs text-gray-500 mb-2" x-show="serviceId" x-cloak>Based on the service you picked — describe your project above and pick anything that applies.</p>
            <p class="text-xs text-gray-500 mb-2" x-show="!serviceId" x-cloak>Pick a service above to see relevant skills.</p>

            <div x-show="serviceId && categories.length" x-cloak class="max-h-56 overflow-y-auto rounded-lg bg-white/5 border border-white/10 p-4 space-y-4">
                <template x-for="[category, catSkills] in categories" :key="category">
                    <div>
                        <div class="font-jetbrains text-[10px] uppercase tracking-widest text-gray-500 mb-2" x-text="category"></div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
                            <template x-for="skill in catSkills" :key="skill.id">
                                <label class="flex items-center gap-2 text-sm text-gray-300">
                                    <input type="checkbox" name="skills[]" :value="skill.id"
                                        :checked="checked.includes(String(skill.id))" @click="toggle(skill.id)"
                                        class="rounded border-white/20 bg-white/5" style="accent-color:#22d3ee">
                                    <span x-text="skill.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <p class="text-xs text-gray-500 mt-2">
                Don't see the skill you need?
                <a href="mailto:hello@undek.com" class="text-cyan-400 hover:text-cyan-300">Email hello@undek.com</a> and we'll sort it out.
            </p>
        </div>

        <div x-data="{
                maxFiles: {{ $uploadRules['max_files'] }},
                maxTotal: {{ $uploadRules['max_total_kb'] * 1024 }},
                warning: '',
                check(input) {
                    const files = [...input.files];
                    const total = files.reduce((sum, f) => sum + f.size, 0);
                    this.warning = files.length > this.maxFiles
                        ? `You can attach at most ${this.maxFiles} files.`
                        : total > this.maxTotal
                            ? `These files add up to ${(total / 1048576).toFixed(1)} MB — the limit is ${Math.round(this.maxTotal / 1048576)} MB in total.`
                            : '';
                },
            }">
            <x-auth.label for="attachments" value="Samples & references (optional)" />
            <input id="attachments" type="file" name="attachments[]" multiple accept="{{ $uploadAccept }}" @change="check($event.target)"
                class="block w-full text-sm text-gray-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-white/10 file:text-gray-100 hover:file:bg-white/20 rounded-lg bg-white/5 border border-white/10">
            <p class="text-xs text-gray-500 mt-2">
                Up to {{ $uploadRules['max_files'] }} files — images (JPG, PNG, WebP, up to 10 MB), videos (MP4, MOV, WebM, up to 50 MB) or documents (PDF, TXT).
                Every file is checked before it's accepted.
            </p>
            <p x-show="warning" x-text="warning" x-cloak class="text-xs text-amber-400 mt-1.5"></p>
            <x-auth.error :messages="$errors->get('attachments')" />
            <x-auth.error :messages="collect($errors->get('attachments.*'))->flatten()->all()" />
        </div>

        @guest
            <div>
                <x-auth.label for="guest_name" :value="__('Your name')" />
                <x-auth.input id="guest_name" type="text" name="guest_name" :value="old('guest_name')" required />
                <x-auth.error :messages="$errors->get('guest_name')" />
            </div>

            <div>
                <x-auth.label for="guest_email" :value="__('Your email')" />
                <x-auth.input id="guest_email" type="email" name="guest_email" :value="old('guest_email')" required placeholder="you@company.com" />
                <x-auth.error :messages="$errors->get('guest_email')" />
            </div>

            <div>
                <x-auth.label for="guest_phone" :value="__('Phone (optional)')" />
                <x-auth.input id="guest_phone" type="text" name="guest_phone" :value="old('guest_phone')" />
                <x-auth.error :messages="$errors->get('guest_phone')" />
            </div>
        @endguest

        <x-auth.button>
            {{ __('Submit request') }} &rarr;
        </x-auth.button>
    </form>
</x-guest-layout>
