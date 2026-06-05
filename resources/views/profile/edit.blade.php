@php
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name ?: $user->email, 0, 1));
    $statusVariant = match ($user->status ?? 'active') {
        'active' => 'green',
        'suspended' => 'orange',
        'blocked' => 'red',
        default => 'slate',
    };
    $authProvider = $user->auth_provider ? \Illuminate\Support\Str::headline($user->auth_provider) : 'Email';
    $latestLogin = $loginActivity->firstWhere('action', 'auth.login') ?? $loginActivity->firstWhere('action', 'auth.social_login');
    $reportCards = [
        ['label' => 'Orders', 'value' => $profileStats['orders'], 'hint' => $profileStats['paid_orders'].' paid, '.$profileStats['pending_orders'].' pending', 'icon' => 'orders', 'tone' => 'blue'],
        ['label' => 'Courses', 'value' => $profileStats['active_courses'].'/'.$profileStats['total_courses'], 'hint' => 'Active academy access', 'icon' => 'academy', 'tone' => 'indigo'],
        ['label' => 'Avg Progress', 'value' => $profileStats['average_progress'].'%', 'hint' => 'Synced learning progress', 'icon' => 'reports', 'tone' => 'green'],
        ['label' => 'Paid Spend', 'value' => 'PKR '.number_format((float) $profileStats['total_spend']), 'hint' => 'Confirmed payments only', 'icon' => 'payment', 'tone' => 'orange'],
    ];
    $activityLabels = [
        'auth.login' => 'Email login',
        'auth.social_login' => 'Social login',
        'auth.registered' => 'Account created',
        'auth.logout' => 'Logged out',
        'checkout.order_created' => 'Order created',
        'payment.paid_webhook' => 'Payment verified',
        'review.submitted' => 'Review submitted',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Account Center</p>
                <h1 class="admin-page-title">Profile & learning intelligence</h1>
                <p class="admin-page-subtitle">Your identity, login activity, course access, reports and security controls in one premium account center.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('dashboard') }}" variant="secondary">My Learning</x-ui.button>
                <x-ui.button href="{{ route('courses.index') }}">Browse Courses</x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container space-y-6">
            <x-ui.card dark padding="p-0" class="overflow-hidden">
                <div class="relative grid gap-6 p-7 lg:grid-cols-[1fr_22rem] lg:items-center">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(96,165,250,.28),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(249,115,22,.18),transparent_30%)]"></div>
                    <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative">
                            @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-[1.75rem] object-cover ring-4 ring-white/10">
                            @else
                                <div class="grid h-24 w-24 place-items-center rounded-[1.75rem] bg-white text-4xl font-black text-blue-700 shadow-2xl shadow-blue-950/20">
                                    {{ $initial }}
                                </div>
                            @endif
                            <span class="absolute -bottom-2 -right-2 grid h-9 w-9 place-items-center rounded-2xl bg-green-400 text-slate-950 shadow-xl">
                                <x-ui.icon name="check" class="h-5 w-5" />
                            </span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :variant="$statusVariant">{{ $user->status ?? 'active' }}</x-ui.badge>
                                <x-ui.badge variant="blue">{{ $authProvider }} account</x-ui.badge>
                                @if($user->email_verified_at)
                                    <x-ui.badge variant="green">Email verified</x-ui.badge>
                                @endif
                            </div>
                            <h2 class="mt-4 break-words text-4xl font-black tracking-[-0.05em] text-white">{{ $user->name }}</h2>
                            <p class="mt-2 break-all text-sm font-semibold text-blue-100">{{ $user->email }}</p>
                            <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <span class="block font-bold text-blue-100">Phone</span>
                                    <strong class="mt-1 block text-white">{{ $user->phone ?: 'Not added yet' }}</strong>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <span class="block font-bold text-blue-100">Academy User ID</span>
                                    <strong class="mt-1 block text-white">{{ $user->moodle_user_id ? '#'.$user->moodle_user_id : 'Pending sync' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 rounded-[1.6rem] border border-white/10 bg-white/10 p-5 shadow-2xl shadow-slate-950/20 backdrop-blur">
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-blue-100">Latest login signal</p>
                        @if($latestLogin)
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-blue-100">Time</span>
                                    <strong class="text-white">{{ $latestLogin->created_at->diffForHumans() }}</strong>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-blue-100">Device</span>
                                    <strong class="text-white">{{ ucfirst($latestLogin->device_type ?? 'unknown') }} · {{ $latestLogin->browser ?? 'Unknown' }}</strong>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-blue-100">Location</span>
                                    <strong class="text-white">{{ $latestLogin->country_name ?? $latestLogin->country_code ?? 'Unknown' }}</strong>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-blue-100">IP</span>
                                    <strong class="break-all text-white">{{ $latestLogin->ip_address ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        @else
                            <p class="mt-4 text-sm font-semibold leading-6 text-blue-100">No login activity has been captured yet.</p>
                        @endif
                    </div>
                </div>
            </x-ui.card>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach($reportCards as $card)
                    <x-ui.card padding="p-5">
                        <div class="flex items-center justify-between gap-3">
                            <x-ui.icon-tile :name="$card['icon']" :tone="$card['tone']" size="sm" />
                            <x-ui.badge variant="slate">Report</x-ui.badge>
                        </div>
                        <p class="mt-5 text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $card['hint'] }}</p>
                    </x-ui.card>
                @endforeach
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="min-w-0 space-y-6">
                    <x-ui.card>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="eyebrow">Course Details</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Learning access & progress</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Paid enrolments, course status and synced progress are shown here.</p>
                            </div>
                            <x-ui.badge variant="indigo">{{ $user->enrollments->count() }} course(s)</x-ui.badge>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse($user->enrollments as $enrollment)
                                @php
                                    $course = $enrollment->course;
                                    $progress = (float) ($user->progress->firstWhere('course_id', $enrollment->course_id)?->progress ?? 0);
                                    $enrollmentVariant = match ($enrollment->status) {
                                        'active' => 'green',
                                        'failed' => 'red',
                                        'pending' => 'orange',
                                        'suspended' => 'slate',
                                        default => 'blue',
                                    };
                                @endphp
                                <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <div class="grid gap-5 lg:grid-cols-[1fr_14rem] lg:items-center">
                                        <div class="min-w-0">
                                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                                <x-ui.badge :variant="$enrollmentVariant">{{ $enrollment->status }}</x-ui.badge>
                                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">AC-{{ $course?->moodle_course_id ?? 'N/A' }}</span>
                                            </div>
                                            <h3 class="break-words text-xl font-black text-slate-950 dark:text-white">{{ $course?->title ?? 'Course unavailable' }}</h3>
                                            <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $course?->short_description ?? 'Course details are not available.' }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                                                <span>{{ $course?->level ?: 'General level' }}</span>
                                                <span>·</span>
                                                <span>{{ $course?->duration ?: 'Self-paced' }}</span>
                                                <span>·</span>
                                                <span>{{ $enrollment->enrolled_at ? 'Enrolled '.$enrollment->enrolled_at->diffForHumans() : 'Waiting access' }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-sm font-black text-slate-700 dark:text-slate-200">
                                                <span>Progress</span>
                                                <span>{{ number_format($progress, 1) }}%</span>
                                            </div>
                                            <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                                <div class="progress-shine h-full rounded-full bg-gradient-to-r from-blue-700 to-indigo-500" style="width: {{ min(100, $progress) }}%"></div>
                                            </div>
                                            @if($course)
                                                <x-ui.button href="{{ route('courses.show', $course) }}" variant="secondary" class="mt-4 w-full px-4 py-2">
                                                    Course details
                                                    <x-ui.icon name="arrow" class="h-4 w-4" />
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <x-ui.empty-state title="No courses yet" description="Purchase a course and your academy access details will appear here." action="Explore Courses" :href="route('courses.index')" />
                            @endforelse
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="eyebrow">Reports</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Orders & payment history</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">A concise account report of recent purchases and payment source status.</p>
                            </div>
                            <x-ui.badge variant="blue">{{ $user->orders->count() }} order(s)</x-ui.badge>
                        </div>

                        <div class="admin-table-wrap premium-scroll mt-6">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Courses</th>
                                        <th>Payment</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($user->orders->sortByDesc('created_at')->take(8) as $order)
                                        @php
                                            $orderVariant = match ($order->status) {
                                                'paid' => 'green',
                                                'pending_verification' => 'blue',
                                                'rejected' => 'red',
                                                'failed', 'cancelled' => 'red',
                                                'pending' => 'orange',
                                                default => 'slate',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}" class="font-black text-blue-700 hover:text-blue-900 dark:text-blue-300">{{ $order->order_no }}</a>
                                            </td>
                                            <td class="max-w-xs text-slate-600 dark:text-slate-300">{{ $order->items->pluck('course.title')->filter()->join(', ') ?: 'No course attached' }}</td>
                                            <td>
                                                <div class="space-y-2">
                                                    <x-ui.badge :variant="$orderVariant">{{ $order->status }}</x-ui.badge>
                                                    @if($order->payment)
                                                        <x-ui.badge :variant="$order->payment->sourceVariant()">{{ $order->payment->sourceLabel() }}</x-ui.badge>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="font-black text-slate-950 dark:text-white">{{ $order->currency }} {{ number_format((float) $order->amount) }}</td>
                                            <td class="text-slate-500 dark:text-slate-400">{{ $order->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <x-ui.empty-state title="No orders yet" description="Your purchase reports will appear after checkout." />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        @include('profile.partials.update-profile-information-form')
                    </x-ui.card>

                    <x-ui.card>
                        @include('profile.partials.update-password-form')
                    </x-ui.card>
                </div>

                <aside class="min-w-0 space-y-6 xl:sticky xl:top-28 xl:self-start">
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="eyebrow">Login Activity</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Recent access</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Device, browser, IP and location signals captured for your account.</p>
                            </div>
                            <x-ui.icon-tile name="shield" tone="green" size="sm" />
                        </div>

                        <div class="mt-6 space-y-3">
                            @forelse($loginActivity as $activity)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black text-slate-950 dark:text-white">{{ $activityLabels[$activity->action] ?? \Illuminate\Support\Str::headline($activity->action) }}</p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                        <x-ui.badge variant="slate">{{ ucfirst($activity->device_type ?? 'device') }}</x-ui.badge>
                                    </div>
                                    <dl class="mt-3 grid gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                        <div class="flex justify-between gap-3"><dt>Browser</dt><dd class="text-right text-slate-950 dark:text-white">{{ $activity->browser ?? 'Unknown' }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt>Platform</dt><dd class="text-right text-slate-950 dark:text-white">{{ $activity->platform ?? 'Unknown' }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt>Country</dt><dd class="text-right text-slate-950 dark:text-white">{{ $activity->country_name ?? $activity->country_code ?? 'Unknown' }}</dd></div>
                                        <div class="flex justify-between gap-3"><dt>IP</dt><dd class="break-all text-right text-slate-950 dark:text-white">{{ $activity->ip_address ?? 'N/A' }}</dd></div>
                                    </dl>
                                </div>
                            @empty
                                <x-ui.empty-state title="No login records yet" description="Login activity will appear after your next sign-in." />
                            @endforelse
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="eyebrow">Activity Trail</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Recent events</h2>
                            </div>
                            <x-ui.icon-tile name="reports" tone="blue" size="sm" />
                        </div>

                        <div class="mt-6 space-y-3">
                            @forelse($activityLogs->take(6) as $activity)
                                <div class="flex gap-3 rounded-2xl border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                    <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">
                                        <x-ui.icon name="activity" class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-950 dark:text-white">{{ $activityLabels[$activity->action] ?? \Illuminate\Support\Str::headline($activity->action) }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $activity->created_at->format('d M Y, h:i A') }}</p>
                                        @if($activity->route_name)
                                            <p class="mt-1 truncate text-xs text-slate-400">{{ $activity->route_name }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <x-ui.empty-state title="No activity yet" description="Orders, reviews and account actions will appear here." />
                            @endforelse
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        @include('profile.partials.delete-user-form')
                    </x-ui.card>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
