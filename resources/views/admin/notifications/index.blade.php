<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Notifications</p>
                <h1 class="admin-page-title">Notification control center</h1>
                <p class="admin-page-subtitle">Create login, registration and purchase messages that automatically appear in the student notification panel.</p>
            </div>
            <x-ui.badge variant="blue">Admin controlled</x-ui.badge>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="space-y-6">
                @if(session('status'))
                    <div class="rounded-3xl border border-green-100 bg-green-50 p-5 font-bold text-green-700 shadow-sm dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                <x-ui.card>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-950 dark:text-white">Create notification rule</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Use tokens like <strong>{name}</strong>, <strong>{course}</strong>, <strong>{order}</strong> and <strong>{date}</strong>.</p>
                        </div>
                        <x-ui.badge variant="indigo">Dynamic</x-ui.badge>
                    </div>

                    <form method="POST" action="{{ route('admin.notifications.store') }}" class="mt-6 grid gap-5 md:grid-cols-2">
                        @csrf

                        <div class="md:col-span-2">
                            <x-input-label for="title" value="Title (optional)" />
                            <x-text-input id="title" name="title" class="mt-2 block w-full" value="{{ old('title') }}" placeholder="Welcome {name}, your learning dashboard is ready" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="message" value="Message (optional)" />
                            <textarea id="message" name="message" rows="4" class="admin-input mt-2 block w-full" placeholder="Write the notification message users will see">{{ old('message') }}</textarea>
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="type" value="Type" />
                            <select id="type" name="type" class="admin-input mt-2 block w-full">
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', 'info') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="audience" value="Audience" />
                            <select id="audience" name="audience" class="admin-input mt-2 block w-full">
                                @foreach($audiences as $value => $label)
                                    <option value="{{ $value }}" @selected(old('audience', 'students') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="trigger_event" value="When to show" />
                            <select id="trigger_event" name="trigger_event" class="admin-input mt-2 block w-full">
                                @foreach($triggers as $value => $label)
                                    <option value="{{ $value }}" @selected(old('trigger_event', 'login') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="starts_at" value="Start time optional" />
                            <x-text-input id="starts_at" name="starts_at" type="datetime-local" class="mt-2 block w-full" value="{{ old('starts_at') }}" />
                        </div>

                        <div>
                            <x-input-label for="ends_at" value="End time optional" />
                            <x-text-input id="ends_at" name="ends_at" type="datetime-local" class="mt-2 block w-full" value="{{ old('ends_at') }}" />
                        </div>

                        <div class="grid gap-3 rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                            <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" checked>
                                Active rule
                            </label>
                            <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200">
                                <input type="checkbox" name="deliver_once" value="1" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" checked>
                                Show once per user
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <x-ui.button type="submit" class="py-3">Create Notification Rule</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>

                <x-ui.card padding="p-0" class="overflow-hidden">
                    @php
                        $statusLabels = [
                            'active' => 'Active',
                            'paused' => 'Paused',
                        ];
                        $hasFilters = collect(['q', 'status'])->contains(fn ($key) => filled(request($key)));
                    @endphp

                    <div class="border-b border-slate-100 p-6 dark:border-slate-800">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Active rules and history</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Filter rules by trigger, audience, timing window, status or creator text.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($hasFilters)
                                    <x-ui.button href="{{ route('admin.notifications.index') }}" variant="secondary">Clear All</x-ui.button>
                                @endif
                                <x-ui.badge variant="blue">{{ $campaigns->total() }} rules</x-ui.badge>
                            </div>
                        </div>

                        <form class="admin-filter mt-6" method="GET">
                            <label class="filter-search relative block">
                                <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search title, message, creator...">
                            </label>
                            <select name="status" class="admin-input">
                                <option value="">Status: all</option>
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.button type="submit" class="filter-action">Apply Filters</x-ui.button>
                        </form>

                        @if($hasFilters)
                            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                                <span>Active filters:</span>
                                @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                                @if(request('status')) <x-ui.badge variant="slate">{{ $statusLabels[request('status')] ?? request('status') }}</x-ui.badge> @endif
                            </div>
                        @endif
                    </div>

                    <div class="admin-table-wrap premium-scroll rounded-none border-0 shadow-none">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Rule</th>
                                    <th class="px-6 py-4">Audience</th>
                                    <th class="px-6 py-4">Trigger</th>
                                    <th class="px-6 py-4">Window</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($campaigns as $campaign)
                                    <tr class="align-top">
                                        <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $campaign->id }}</td>
                                        <td>
                                            <div class="font-black text-slate-950 dark:text-white">{{ $campaign->title }}</div>
                                            <div class="mt-1 max-w-md text-xs leading-5 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($campaign->message, 120) }}</div>
                                        </td>
                                        <td class="font-bold text-slate-600 dark:text-slate-300">{{ $audiences[$campaign->audience] ?? $campaign->audience }}</td>
                                        <td class="font-bold text-slate-600 dark:text-slate-300">{{ $triggers[$campaign->trigger_event] ?? $campaign->trigger_event }}</td>
                                        <td class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                            <div>{{ $campaign->starts_at?->format('d M Y H:i') ?? 'Starts now' }}</div>
                                            <div>{{ $campaign->ends_at?->format('d M Y H:i') ?? 'No end date' }}</div>
                                        </td>
                                        <td>
                                            <x-ui.badge :variant="$campaign->is_active ? 'green' : 'orange'">{{ $campaign->is_active ? 'Active' : 'Paused' }}</x-ui.badge>
                                        </td>
                                        <td>
                                            <div class="admin-actions">
                                                <a href="{{ route('admin.notifications.edit', $campaign) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 transition hover:border-blue-200 hover:text-blue-700 dark:border-slate-800 dark:text-slate-200">Edit</a>
                                                <form method="POST" action="{{ route('admin.notifications.toggle', $campaign) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 transition hover:border-orange-200 hover:text-orange-700 dark:border-slate-800 dark:text-slate-200">{{ $campaign->is_active ? 'Pause' : 'Activate' }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.notifications.destroy', $campaign) }}" onsubmit="return confirm('Delete this notification rule?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-xl border border-red-100 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/40">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <x-ui.empty-state title="No notification rules yet" description="Create a rule above to show messages on login, registration or purchase fulfilment." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-100 p-5 dark:border-slate-800">
                        {{ $campaigns->links() }}
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
