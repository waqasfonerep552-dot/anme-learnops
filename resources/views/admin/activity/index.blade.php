<x-app-layout>
    {{-- Activity log: important system events admin audit ke liye show hote hain. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Activity</p>
            <h1 class="admin-page-title">Activity logs</h1>
            <p class="admin-page-subtitle">Operational events from orders, payments, academy fulfilment and admin actions.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <x-ui.card>
                @php
                    $hasFilters = collect(['q', 'action'])->contains(fn ($key) => filled(request($key)));
                @endphp

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Audit timeline</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Useful for debugging login history, IP addresses, devices, payments, enrolments and admin actions.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($hasFilters)
                            <x-ui.button href="{{ route('admin.activity.index') }}" variant="secondary">Clear All</x-ui.button>
                        @endif
                        <x-ui.badge variant="slate">{{ $logs->total() }} events</x-ui.badge>
                    </div>
                </div>

                <form method="GET" class="admin-filter mt-6">
                    <label class="filter-search relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search action, user, email, IP, country...">
                    </label>
                    <select name="action" class="admin-input">
                        <option value="">All actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" class="filter-action">Apply Filters</x-ui.button>
                </form>

                @if($hasFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <span>Active filters:</span>
                        @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                        @if(request('action')) <x-ui.badge variant="indigo">{{ request('action') }}</x-ui.badge> @endif
                    </div>
                @endif

                <div class="admin-table-wrap premium-scroll mt-6">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Location</th>
                                <th>Device</th>
                                <th>Payload</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                @php
                                    $isSystemEvent = blank($log->ip_address) && blank($log->user_agent) && blank($log->http_method);
                                    $locationTitle = $isSystemEvent ? 'System job' : ($log->ip_address ?: 'No IP captured');
                                    $locationMeta = $isSystemEvent
                                        ? 'Background process'
                                        : ($log->country_name ?: 'Unknown country').($log->city ? ' · '.$log->city : '');
                                    $deviceTitle = $isSystemEvent ? 'Server automation' : ucfirst($log->device_type ?: 'Unknown');
                                    $deviceMeta = $isSystemEvent
                                        ? 'Queue / scheduled task'
                                        : ($log->browser ?: 'Unknown browser').' · '.($log->platform ?: 'Unknown platform');
                                    $userMeta = $log->user
                                        ? ($isSystemEvent ? 'System event for '.$log->user->email : $log->user->email)
                                        : 'Automated event';
                                @endphp
                                <tr>
                                    <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $log->id }}</td>
                                    <td>
                                        <x-ui.badge variant="indigo">{{ $log->action }}</x-ui.badge>
                                    </td>
                                    <td>
                                        <div class="font-black text-slate-950 dark:text-white">{{ $log->user?->name ?? 'System' }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $userMeta }}</div>
                                    </td>
                                    <td>
                                        <div class="font-black text-slate-950 dark:text-white">{{ $locationTitle }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $locationMeta }}</div>
                                    </td>
                                    <td>
                                        <div class="font-black text-slate-950 dark:text-white">{{ $deviceTitle }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $deviceMeta }}</div>
                                    </td>
                                    <td class="max-w-xl">
                                        <code class="soft-kbd whitespace-normal break-words">
                                            {{ \Illuminate\Support\Str::limit(json_encode($log->payload), 180) }}
                                        </code>
                                        @if($log->route_name || $log->http_method)
                                            <div class="mt-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                                {{ $log->http_method }} {{ $log->route_name ?? $log->url }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-slate-500 dark:text-slate-400">{{ $log->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <x-ui.empty-state title="No activity logs yet" description="System events will appear here after orders, payments or academy sync jobs run." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $logs->links() }}</div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>

