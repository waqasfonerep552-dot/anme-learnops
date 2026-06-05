<x-app-layout>
    {{-- Read-only launch checklist. Secrets are never printed here. --}}
    @php
        $statusVariants = [
            'ok' => 'green',
            'warn' => 'orange',
            'danger' => 'red',
        ];
        $statusLabels = [
            'ok' => 'OK',
            'warn' => 'Check',
            'danger' => 'Fix',
        ];
        $launchBlocked = ($summary['danger'] ?? 0) > 0;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Production Readiness</p>
                <h1 class="admin-page-title">Launch checklist</h1>
                <p class="admin-page-subtitle">Read-only health view for app config, payment safety, queue operations and Moodle fulfilment.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('admin.settings.index') }}" variant="secondary">
                    <x-ui.icon name="settings" class="h-4 w-4" />
                    Settings
                </x-ui.button>
                <x-ui.button href="{{ route('admin.settings.moodle-diagnostics') }}" variant="accent">
                    <x-ui.icon name="shield" class="h-4 w-4" />
                    Test Academy API
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="min-w-0 space-y-6">
                <x-ui.card dark>
                    <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-blue-200">Launch status</p>
                            <h2 class="mt-3 text-3xl font-black">
                                {{ $launchBlocked ? 'Needs fixes before live payments' : 'No blocking issues found' }}
                            </h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                                Green checks are ready. Orange items need operational attention. Red items should be fixed before public production launch.
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-3xl font-black text-green-300">{{ $summary['ok'] }}</p>
                                <p class="mt-1 text-xs font-black uppercase tracking-widest text-slate-300">Ready</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-3xl font-black text-orange-300">{{ $summary['warn'] }}</p>
                                <p class="mt-1 text-xs font-black uppercase tracking-widest text-slate-300">Check</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-3xl font-black text-red-300">{{ $summary['danger'] }}</p>
                                <p class="mt-1 text-xs font-black uppercase tracking-widest text-slate-300">Fix</p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="grid gap-6 xl:grid-cols-2">
                    @foreach($sections as $section)
                        <x-ui.card padding="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <x-ui.icon-tile :name="$section['icon']" tone="blue" size="sm" />
                                    <div class="min-w-0">
                                        <h2 class="text-2xl font-black text-slate-950 dark:text-white">{{ $section['title'] }}</h2>
                                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-500 dark:text-slate-400">{{ $section['hint'] }}</p>
                                    </div>
                                </div>
                                <x-ui.badge variant="slate">{{ count($section['checks']) }} checks</x-ui.badge>
                            </div>

                            <div class="mt-6 space-y-3">
                                @foreach($section['checks'] as $check)
                                    @php($variant = $statusVariants[$check['status']] ?? 'slate')
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="font-black text-slate-950 dark:text-white">{{ $check['label'] }}</h3>
                                                    <x-ui.badge :variant="$variant">{{ $statusLabels[$check['status']] ?? $check['status'] }}</x-ui.badge>
                                                </div>
                                                <p class="mt-2 break-all text-sm font-black text-blue-700 dark:text-blue-300">{{ $check['value'] }}</p>
                                                <p class="mt-2 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ $check['hint'] }}</p>
                                            </div>
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $check['status'] === 'ok' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-200' : ($check['status'] === 'danger' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200' : 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-200') }}">
                                                <x-ui.icon :name="$check['status'] === 'ok' ? 'check' : 'settings'" class="h-5 w-5" />
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
