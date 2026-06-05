<x-app-layout>
    {{-- Reports: management ko revenue, enrolment aur course performance ka decision view deta hai. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Reports</p>
            <h1 class="admin-page-title">Business reporting</h1>
            <p class="admin-page-subtitle">Revenue, payment, enrolment and course performance health for decision makers.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    @foreach([
                        ['Students', $totalStudents, 'blue'],
                        ['Revenue', 'PKR '.number_format((float) $totalRevenue), 'green'],
                        ['Paid Payments', $paidPayments, 'indigo'],
                        ['Pending Orders', $pendingOrders, 'orange'],
                        ['Failed Enrolments', $failedEnrollments, 'red'],
                    ] as $metric)
                        <div class="mini-stat">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $metric[0] }}</p>
                                <x-ui.badge :variant="$metric[2]">Live</x-ui.badge>
                            </div>
                            <p class="mt-4 text-3xl font-black text-slate-950 dark:text-white">{{ $metric[1] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                    <x-ui.card>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Course performance</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Top courses by active enrolments.</p>
                            </div>
                            <x-ui.badge variant="blue">Top 10</x-ui.badge>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse($coursePerformance as $course)
                                @php
                                    $width = min(100, max(8, $course->active_enrollments_count * 10));
                                @endphp
                                <div class="rounded-[1.35rem] border border-slate-100 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <h3 class="font-black text-slate-950 dark:text-white">{{ $course->title }}</h3>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                Total enrolments: {{ $course->enrollments_count }} | Active: {{ $course->active_enrollments_count }}
                                            </p>
                                        </div>
                                        <strong class="text-2xl text-blue-700 dark:text-blue-300">{{ $course->active_enrollments_count }}</strong>
                                    </div>
                                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-white ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-slate-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-blue-700 to-indigo-600" style="width: {{ $width }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <x-ui.empty-state title="No course performance yet" description="Data appears after successful enrolments are recorded." />
                            @endforelse
                        </div>
                    </x-ui.card>

                    <x-ui.card dark>
                        <p class="text-xs font-black uppercase tracking-widest text-blue-200">Operations Signal</p>
                        <h2 class="mt-3 text-3xl font-black">What to watch</h2>
                        <div class="mt-6 space-y-4 text-sm leading-6 text-slate-300">
                            <div class="rounded-2xl bg-white/5 p-4">
                                <strong class="block text-white">Payment friction</strong>
                                <span>Pending orders show where Easypaisa confirmation needs follow-up.</span>
                            </div>
                            <div class="rounded-2xl bg-white/5 p-4">
                                <strong class="block text-white">Academy fulfilment</strong>
                                <span>Failed enrolments should be retried before students contact support.</span>
                            </div>
                            <div class="rounded-2xl bg-white/5 p-4">
                                <strong class="block text-white">Course demand</strong>
                                <span>Active enrolments help decide which courses deserve homepage placement.</span>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
