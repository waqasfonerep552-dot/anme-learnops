<x-app-layout>
    {{-- Admin dashboard: revenue, orders, fulfilment aur learning progress ka executive workspace. --}}
    @php
        $revenueTrendTotal = (float) collect($revenueTrend)->sum('value');
        $syncedProgressLearners = (int) collect($progressBuckets)->sum('count');
    @endphp

    <x-slot name="header">
        <div class="page-hero">
            <div class="relative z-10 grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="eyebrow">Admin Dashboard</p>
                    <h1 class="admin-page-title mt-2">Business intelligence center</h1>
                    <p class="admin-page-subtitle">Sales, academy access, learner progress and support risks in one premium operations view.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="green">System Online</x-ui.badge>
                    <x-ui.badge variant="blue">{{ $dashboardPeriod }} day view</x-ui.badge>
                    <x-ui.badge :variant="$failedEnrollments ? 'orange' : 'green'">{{ $failedEnrollments }} fulfilment issues</x-ui.badge>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="app-container py-10">
        <div class="admin-shell">
            <x-admin.sidebar />

            <div class="admin-dashboard-stack">
                <section class="dasher-command-panel">
                    <div class="dasher-command-copy">
                        <span class="dasher-chip">
                            <x-ui.icon name="activity" class="h-4 w-4" />
                            Operations cockpit
                        </span>
                        <h2>Clear signals for revenue, access and learner momentum.</h2>
                        <p>Monitor payments, academy fulfilment, public course readiness and learning progress before students need support.</p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <x-ui.button :href="route('admin.orders.index')" variant="secondary">Review Orders</x-ui.button>
                            <x-ui.button :href="route('admin.reports.index')" variant="orange">Open Reports</x-ui.button>
                        </div>
                    </div>

                    <div class="dasher-command-grid">
                        @foreach([
                            ['label' => 'Period Revenue', 'value' => 'PKR '.number_format((float) $periodRevenue), 'tone' => 'green'],
                            ['label' => 'Low Progress', 'value' => $lowProgressLearners.' learners', 'tone' => $lowProgressLearners ? 'orange' : 'green'],
                            ['label' => 'Public Courses', 'value' => $publicCourses.'/'.$courses, 'tone' => 'blue'],
                            ['label' => 'Suspended Access', 'value' => $suspendedEnrollments.' records', 'tone' => $suspendedEnrollments ? 'orange' : 'green'],
                            ['label' => 'Orders', 'value' => $paidOrders.' paid', 'tone' => 'indigo'],
                        ] as $signal)
                            <div class="dasher-command-stat dasher-command-stat-{{ $signal['tone'] }}">
                                <span class="dasher-stat-dot"></span>
                                <p>{{ $signal['label'] }}</p>
                                <strong>{{ $signal['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="admin-kpi-grid">
                    <x-dashboard.stat-card label="Students" :value="$students" description="Registered student accounts" tone="blue" icon="students" />
                    <x-dashboard.stat-card label="Revenue" :value="'PKR '.number_format((float) $revenue)" :description="'PKR '.number_format((float) $periodRevenue).' in current view'" tone="green" icon="payment" />
                    <x-dashboard.stat-card label="Active Access" :value="$activeEnrollments" :description="$enrollments.' total fulfilment records'" tone="orange" icon="academy" />
                    <x-dashboard.stat-card label="Suspended Access" :value="$suspendedEnrollments" description="Admin-paused academy records" tone="slate" icon="shield" />
                    <x-dashboard.stat-card label="Public Courses" :value="$publicCourses" :description="$courses.' total catalog records'" tone="slate" icon="courses" />
                    <x-dashboard.stat-card label="Orders" :value="$totalOrders" :description="$paidOrders.' paid, '.$pendingOrders.' pending'" tone="indigo" icon="orders" />
                </section>

                @if($analyticsEnabled)
                    <section class="admin-analytics-grid">
                        <x-ui.card padding="p-7" class="analytics-card analytics-card-blue h-full overflow-hidden">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="eyebrow">Revenue Analytics</p>
                                    <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Monthly payment trend</h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Paid Easypaisa payments grouped by month for quick sales analysis.</p>
                                </div>
                                <x-ui.badge variant="green">PKR {{ number_format((float) collect($revenueTrend)->sum('value')) }}</x-ui.badge>
                            </div>

                            @if($revenueTrendTotal > 0)
                                <div class="chart-stage mt-6 grid h-56 grid-cols-6 items-end gap-3">
                                    @foreach($revenueTrend as $item)
                                        <div class="flex h-full min-w-0 flex-col justify-end gap-3">
                                            <div class="relative flex flex-1 items-end justify-center">
                                                <div class="dashboard-bar group w-full max-w-16 rounded-t-3xl bg-gradient-to-t from-blue-700 via-indigo-600 to-cyan-400 shadow-xl shadow-blue-900/20" style="height: {{ $item['height'] }}%">
                                                    <span class="absolute -top-8 left-1/2 hidden -translate-x-1/2 rounded-full bg-slate-950 px-3 py-1 text-xs font-black text-white shadow-xl group-hover:block">
                                                        PKR {{ number_format((float) $item['value']) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                                                <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-200">{{ number_format((float) $item['value']) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-6">
                                    <x-ui.empty-state
                                        title="No paid revenue yet"
                                        description="Paid Easypaisa payments will build this monthly trend automatically."
                                        action="Review orders"
                                        :href="route('admin.orders.index')"
                                    />
                                </div>
                            @endif
                        </x-ui.card>

                        <x-ui.card padding="p-7" class="analytics-card analytics-card-indigo h-full">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="eyebrow">Learning Analytics</p>
                                    <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Learning performance matrix</h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">One view for average progress, completion bands, course momentum and support risk.</p>
                                </div>
                            </div>

                            <div class="dashboard-metric-grid mt-5">
                                @foreach([
                                    ['label' => 'Average', 'value' => $averageProgress.'%', 'tone' => 'blue'],
                                    ['label' => 'At Risk', 'value' => $lowProgressLearners, 'tone' => $lowProgressLearners ? 'orange' : 'green'],
                                    ['label' => 'Synced', 'value' => $syncedProgressLearners, 'tone' => 'indigo'],
                                ] as $metric)
                                    <div class="progress-metric progress-metric-{{ $metric['tone'] }}">
                                        <span>{{ $metric['label'] }}</span>
                                        <strong>{{ $metric['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>

                            <div class="progress-matrix mt-5">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Completion bands</p>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:bg-slate-900 dark:text-slate-400">Distribution</span>
                                    </div>

                                    @foreach($progressBuckets as $bucket)
                                        <div class="progress-band-row">
                                            <div class="flex items-center justify-between gap-3">
                                                <span>{{ $bucket['label'] }}</span>
                                                <strong>{{ $bucket['count'] }} learners</strong>
                                            </div>
                                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-900">
                                                <div class="progress-shine h-full rounded-full {{ match($bucket['tone']) { 'red' => 'bg-red-500', 'orange' => 'bg-orange-500', 'green' => 'bg-green-500', 'indigo' => 'bg-indigo-500', default => 'bg-blue-600' } }}" style="width: {{ $bucket['count'] > 0 ? max(4, $bucket['percent']) : 0 }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Course momentum</p>
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">Top synced</span>
                                    </div>

                                    @forelse($topProgressCourses as $course)
                                        <a href="{{ route('courses.show', $course['slug']) }}" class="course-momentum-row">
                                            <div class="min-w-0">
                                                <p class="truncate font-black text-slate-950 dark:text-white">{{ $course['title'] }}</p>
                                                <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $course['learners'] }} learners</p>
                                            </div>
                                            <div class="w-24 shrink-0">
                                                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-900">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-700 to-indigo-500" style="width: {{ min(100, $course['average']) }}%"></div>
                                                </div>
                                                <p class="mt-1 text-right text-xs font-black text-blue-700 dark:text-blue-300">{{ $course['average'] }}%</p>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="rounded-3xl border border-dashed border-slate-200 p-5 text-sm font-semibold text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                            No course progress synced yet.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-7 rounded-3xl border border-orange-100 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/30">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon-tile name="reports" tone="orange" size="sm" />
                                    <div>
                                        <p class="font-black text-slate-950 dark:text-white">{{ $lowProgressLearners }} learners below {{ $lowProgressThreshold }}%</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-300">Use this as a support watchlist for slow or stuck learners.</p>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    </section>

                    <section class="admin-two-card-grid">
                        <x-ui.card class="admin-action-card h-full">
                            <p class="eyebrow">Status Mix</p>
                            <h3 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Orders & access</h3>
                            <div class="mt-5 space-y-5">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Orders</p>
                                    <div class="mt-3 space-y-3">
                                        @foreach($orderStatusBreakdown as $status)
                                            <div class="status-row">
                                                <div class="flex justify-between text-sm font-black">
                                                    <span>{{ $status['label'] }}</span><span>{{ $status['count'] }}</span>
                                                </div>
                                                <div class="mt-2 h-2 rounded-full bg-slate-100 dark:bg-slate-900"><div class="h-full rounded-full bg-blue-700" style="width: {{ $status['count'] > 0 ? max(5, $status['percent']) : 0 }}%"></div></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Academy Access</p>
                                    <div class="mt-3 space-y-3">
                                        @foreach($enrollmentStatusBreakdown as $status)
                                            <div class="status-row">
                                                <div class="flex justify-between text-sm font-black">
                                                    <span>{{ $status['label'] }}</span><span>{{ $status['count'] }}</span>
                                                </div>
                                                <div class="mt-2 h-2 rounded-full bg-slate-100 dark:bg-slate-900"><div class="h-full rounded-full bg-indigo-600" style="width: {{ $status['count'] > 0 ? max(5, $status['percent']) : 0 }}%"></div></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card class="admin-action-card h-full">
                            <p class="eyebrow">Quick Actions</p>
                            <h3 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Move faster</h3>
                            <div class="mt-5 grid gap-3">
                                <x-ui.button :href="route('admin.courses.index')" variant="secondary" class="justify-between"><span class="flex items-center gap-2"><x-ui.icon name="courses" class="h-4 w-4" /> Manage Courses</span><x-ui.icon name="arrow" class="h-4 w-4" /></x-ui.button>
                                <x-ui.button :href="route('admin.orders.index')" variant="secondary" class="justify-between"><span class="flex items-center gap-2"><x-ui.icon name="orders" class="h-4 w-4" /> Review Orders</span><x-ui.icon name="arrow" class="h-4 w-4" /></x-ui.button>
                                <x-ui.button :href="route('admin.reports.index')" variant="secondary" class="justify-between"><span class="flex items-center gap-2"><x-ui.icon name="reports" class="h-4 w-4" /> Open Reports</span><x-ui.icon name="arrow" class="h-4 w-4" /></x-ui.button>
                                <x-ui.button :href="route('admin.settings.index') . '#analytics'" variant="secondary" class="justify-between"><span class="flex items-center gap-2"><x-ui.icon name="settings" class="h-4 w-4" /> Analytics Settings</span><x-ui.icon name="arrow" class="h-4 w-4" /></x-ui.button>
                            </div>
                        </x-ui.card>
                    </section>
                @else
                    <x-ui.card>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="eyebrow">Analytics Paused</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Admin analytics panel is disabled.</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Enable it from settings when you want charts and progress intelligence visible.</p>
                            </div>
                            <x-ui.button href="{{ route('admin.settings.index') }}#analytics">Open Settings</x-ui.button>
                        </div>
                    </x-ui.card>
                @endif

                <section class="admin-feed-grid">
                    <x-ui.card padding="p-0" class="h-full overflow-hidden">
                        <div class="flex flex-col gap-3 border-b border-slate-100 p-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="eyebrow">Sales Feed</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Latest orders</h2>
                            </div>
                            <x-ui.badge variant="slate">Last 10</x-ui.badge>
                        </div>

                        <div class="admin-table-wrap premium-scroll rounded-none border-0 shadow-none">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Order</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php
                                            $orderVariant = match ($order->payment?->status ?? $order->status) {
                                                'paid' => 'green',
                                                'pending_verification' => 'blue',
                                                'rejected', 'failed', 'cancelled' => 'red',
                                                default => 'orange',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $order->id }}</td>
                                            <td class="font-black text-blue-700"><a href="{{ route('orders.show', $order) }}">{{ $order->order_no }}</a></td>
                                            <td class="font-semibold text-slate-700 dark:text-slate-200">{{ $order->user?->name }}</td>
                                            <td class="font-black text-slate-950 dark:text-white">{{ $order->currency }} {{ number_format((float) $order->amount) }}</td>
                                            <td>
                                                <x-ui.badge :variant="$orderVariant">{{ $order->payment?->status ?? $order->status }}</x-ui.badge>
                                            </td>
                                            <td class="text-slate-500 dark:text-slate-400">{{ $order->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">
                                                <x-ui.empty-state title="No orders yet" description="Completed checkouts and Easypaisa payments will appear here." />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>

                    <x-ui.card dark class="h-full">
                        <p class="text-xs font-black uppercase tracking-widest text-blue-200">Operational Readiness</p>
                        <h3 class="mt-2 text-2xl font-black text-white">Health checklist</h3>
                        <div class="mt-5 space-y-3">
                            @foreach([
                                ['Course catalog', $publicCourses > 0 ? 'Ready' : 'Needs Approval', $publicCourses > 0],
                                ['Payment records', $paidOrders > 0 ? 'Receiving' : 'Waiting', true],
                                ['Access fulfilment', $failedEnrollments === 0 ? 'Healthy' : 'Needs Review', $failedEnrollments === 0],
                                ['Progress sync', $syncedProgressLearners > 0 ? 'Synced' : 'No Data', $syncedProgressLearners > 0],
                            ] as $item)
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <span class="flex items-center gap-3 text-sm font-bold text-blue-50">
                                        <span class="grid h-9 w-9 place-items-center rounded-xl {{ $item[2] ? 'bg-green-400/15 text-green-300' : 'bg-orange-400/15 text-orange-300' }}">
                                            <x-ui.icon :name="$item[2] ? 'check' : 'settings'" class="h-4 w-4" />
                                        </span>
                                        {{ $item[0] }}
                                    </span>
                                    <span class="rounded-full px-3 py-1 text-xs font-black uppercase {{ $item[2] ? 'bg-green-400/15 text-green-300' : 'bg-orange-400/15 text-orange-300' }}">{{ $item[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
