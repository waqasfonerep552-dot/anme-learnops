<x-app-layout>
    {{-- Academy API diagnostics read-only health check hai; yahan koi user/course modify nahi hota. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Academy API Integration</p>
            <h1 class="admin-page-title">Connection diagnostics</h1>
            <p class="admin-page-subtitle">Use this page after adding your academy API token in `.env` to confirm required webservice functions are reachable.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="space-y-6">
                <x-ui.card>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Base URL</p>
                            <p class="mt-2 break-all font-black text-slate-950 dark:text-white">{{ $baseUrl }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">REST URL</p>
                            <p class="mt-2 break-all font-black text-slate-950 dark:text-white">{{ $restUrl }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Token</p>
                            <p class="mt-2">
                                <x-ui.badge :variant="$tokenConfigured ? 'green' : 'red'">{{ $tokenConfigured ? 'Configured' : 'Missing' }}</x-ui.badge>
                            </p>
                        </div>
                    </div>

                    @unless($tokenConfigured)
                        <div class="mt-6 rounded-3xl border border-orange-100 bg-orange-50 p-5 text-sm font-semibold leading-6 text-orange-800 dark:border-orange-900 dark:bg-orange-950/30 dark:text-orange-200">
                            Add your academy API token in `.env` as <span class="font-black">MOODLE_WS_TOKEN=your_token_here</span>, then run <span class="font-black">php artisan config:clear</span>.
                        </div>
                    @endunless
                </x-ui.card>

                <x-ui.card>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-950 dark:text-white">API checks</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">These calls verify API token, REST protocol, service permissions and function availability.</p>
                        </div>
                        <x-ui.button href="{{ route('admin.settings.moodle-diagnostics') }}" variant="secondary">Run Again</x-ui.button>
                    </div>

                    <div class="admin-table-wrap premium-scroll mt-6">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Check</th>
                                    <th>Function</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checks as $check)
                                    <tr>
                                        <td class="cell-compact font-black text-slate-500 dark:text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="font-black text-slate-950 dark:text-white">{{ $check['name'] }}</td>
                                        <td><code class="soft-kbd">{{ $check['function'] }}</code></td>
                                        <td class="capitalize text-slate-600 dark:text-slate-300">{{ $check['type'] }}</td>
                                        <td><x-ui.badge :variant="$check['status'] === 'ok' ? 'green' : 'red'">{{ $check['status'] }}</x-ui.badge></td>
                                        <td class="text-slate-600 dark:text-slate-300">{{ $check['message'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-ui.empty-state title="Token missing" description="Diagnostics will run after MOODLE_WS_TOKEN is configured." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Configured functions</h2>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach($functions as $key => $function)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ str_replace('_', ' ', $key) }}</p>
                                <p class="mt-2 break-all font-black text-slate-950 dark:text-white">{{ $function }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
