<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">Notifications</p>
            <h1 class="admin-page-title">Edit notification rule</h1>
            <p class="admin-page-subtitle">Update message, audience, trigger and delivery window.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <x-ui.card>
                <form method="POST" action="{{ route('admin.notifications.update', $campaign) }}" class="grid gap-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div class="md:col-span-2">
                        <x-input-label for="title" value="Title (optional)" />
                        <x-text-input id="title" name="title" class="mt-2 block w-full" value="{{ old('title', $campaign->title) }}" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="message" value="Message (optional)" />
                        <textarea id="message" name="message" rows="5" class="admin-input mt-2 block w-full">{{ old('message', $campaign->message) }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="admin-input mt-2 block w-full">
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $campaign->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="audience" value="Audience" />
                        <select id="audience" name="audience" class="admin-input mt-2 block w-full">
                            @foreach($audiences as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience', $campaign->audience) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="trigger_event" value="When to show" />
                        <select id="trigger_event" name="trigger_event" class="admin-input mt-2 block w-full">
                            @foreach($triggers as $value => $label)
                                <option value="{{ $value }}" @selected(old('trigger_event', $campaign->trigger_event) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="starts_at" value="Start time optional" />
                        <x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-2 block w-full" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d\\TH:i')) }}" />
                    </div>

                    <div>
                        <x-input-label for="ends_at" value="End time optional" />
                        <x-text-input id="ends_at" name="ends_at" type="datetime-local" class="mt-2 block w-full" value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d\\TH:i')) }}" />
                    </div>

                    <div class="grid gap-3 rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('is_active', $campaign->is_active))>
                            Active rule
                        </label>
                        <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="deliver_once" value="1" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('deliver_once', $campaign->deliver_once))>
                            Show once per user
                        </label>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <x-ui.button type="submit" class="py-3">Save Changes</x-ui.button>
                        <x-ui.button :href="route('admin.notifications.index')" variant="secondary" class="py-3">Back to Notifications</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
