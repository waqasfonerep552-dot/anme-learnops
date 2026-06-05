<x-app-layout>
    {{-- Student dashboard: orders, enrolments, progress aur payment history ka private view. --}}
    @php
        $activeEnrollments = $user->enrollments->where('status', 'active');
        $setupEnrollments = $user->enrollments->where('status', 'setup_required');
        $pendingEnrollments = $user->enrollments->whereIn('status', ['pending', 'failed']);
        $suspendedEnrollments = $user->enrollments->where('status', 'suspended');
        $hasPaidOrderAwaitingFirstSetup = $user->orders->where('status', 'paid')->isNotEmpty() && ! $user->hasAcademyPassword();
        $hasSetupRequiredEnrollment = $setupEnrollments->isNotEmpty();
        $hasBackendSetupFlag = filled($user->academy_setup_required_at);
        $needsAcademySetup = $hasPaidOrderAwaitingFirstSetup || $hasSetupRequiredEnrollment || $hasBackendSetupFlag;
        $recentOrders = $user->orders->take(5);
        $averageProgress = $user->progress->count() ? round((float) $user->progress->avg('progress'), 1) : 0;
        $latestActiveEnrollment = $activeEnrollments->sortByDesc('updated_at')->first();
        $academyBaseUrl = rtrim((string) config('moodle.base_url'), '/');
        $academyLoginReady = $user->hasAcademyPassword() && filled($user->moodle_user_id);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">My Learning</p>
                <h1 class="mt-2 text-4xl font-black tracking-[-0.04em] text-slate-950 dark:text-white">Welcome back, {{ $user->name }}</h1>
                <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-300">
                    Course access, payment updates, progress and ANME Academy launch links — sab ek clean dashboard mein.
                </p>
            </div>

            @if($latestActiveEnrollment?->course?->moodle_course_id)
                <x-ui.button
                    href="{{ $academyBaseUrl }}/course/view.php?id={{ $latestActiveEnrollment->course->moodle_course_id }}"
                    target="_blank"
                    rel="noopener"
                >
                    Continue Learning
                    <x-ui.icon name="arrow" class="h-4 w-4" />
                </x-ui.button>
            @else
                <x-ui.button :href="route('courses.index')">Browse Courses</x-ui.button>
            @endif
        </div>
    </x-slot>

    <div class="app-container py-10">
        @if($needsAcademySetup)
            <x-ui.card class="mb-8 border-blue-200 bg-gradient-to-r from-blue-50 via-indigo-50 to-white dark:border-blue-900 dark:from-blue-950/40 dark:via-indigo-950/30 dark:to-slate-950">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <x-ui.icon-tile name="shield" tone="blue" />
                        <div>
                            <p class="eyebrow">Action Required</p>
                            <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Set your ANME Academy login</h2>
                            <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                Payment approve ho chuki hai. Course access activate karne ke liye apna Academy username aur strong password set karein.
                                Ye step sirf pehli approved purchase par aata hai.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <x-ui.badge variant="green">Payment approved</x-ui.badge>
                                <x-ui.badge variant="blue">Login setup pending</x-ui.badge>
                                <x-ui.badge variant="orange">Access waiting</x-ui.badge>
                            </div>
                        </div>
                    </div>
                    <x-ui.button href="{{ route('academy-access.edit') }}" class="shrink-0">
                        Set Academy Login
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </x-ui.button>
                </div>
            </x-ui.card>
        @elseif($activeEnrollments->isNotEmpty())
            <x-ui.card dark class="mb-8 overflow-hidden">
                <div class="relative">
                    <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-blue-500/20 blur-3xl"></div>
                    <div class="absolute -bottom-24 left-20 h-56 w-56 rounded-full bg-indigo-500/20 blur-3xl"></div>
                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <x-ui.icon-tile name="academy" tone="green" />
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.3em] text-blue-200">Access Ready</p>
                                <h2 class="mt-2 text-3xl font-black tracking-tight text-white">Your Academy access is active</h2>
                                <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-blue-50/80">
                                    Aapka learning access ready hai. Same Academy account future purchases ke liye bhi use hoga — new username/password dobara nahi chahiye.
                                </p>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/10 p-4 text-sm font-bold text-blue-50">
                            <p class="text-xs uppercase tracking-widest text-blue-200">Academy Username</p>
                            <p class="mt-1 text-xl font-black text-white">{{ $user->academy_username ?? 'Ready' }}</p>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @endif

        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card label="Active Courses" :value="$activeEnrollments->count()" description="Ready to open in ANME Academy" tone="blue" icon="academy" />
            <x-dashboard.stat-card label="Average Progress" :value="$averageProgress.'%'" description="Synced learning completion" tone="green" icon="reports" />
            <x-dashboard.stat-card label="Orders" :value="$user->orders->count()" description="Purchases and Easypaisa records" tone="slate" icon="orders" />
            <x-dashboard.stat-card label="Academy Login" :value="$academyLoginReady ? 'Ready' : 'Pending'" description="$user->academy_username ?? 'Setup after approval'" tone="orange" icon="shield" />
        </section>

        <section class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <x-ui.card padding="p-0" class="overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-6 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="eyebrow">Learning Workspace</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">My courses</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Access status, progress and next action har course ke saath clear hai.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.badge variant="green">{{ $activeEnrollments->count() }} active</x-ui.badge>
                        @if($pendingEnrollments->isNotEmpty())
                            <x-ui.badge variant="orange">{{ $pendingEnrollments->count() }} needs review</x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="grid gap-5 p-6">
                    @forelse($user->enrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $progress = $user->progress->firstWhere('course_id', $enrollment->course_id)?->progress ?? 0;
                            $statusVariant = match ($enrollment->status) {
                                'active' => 'green',
                                'failed' => 'red',
                                'setup_required' => 'blue',
                                'suspended' => 'orange',
                                'pending' => 'orange',
                                default => 'slate',
                            };
                            $statusCopy = match ($enrollment->status) {
                                'active' => 'Access active hai — course open karke learning continue karein.',
                                'setup_required' => 'Payment approve hai, ab Academy login setup complete karna hai.',
                                'failed' => 'Access activation fail hui hai. Admin review/ retry ki zaroorat hai.',
                                'suspended' => 'Is course ka access admin ne temporarily pause kiya hua hai.',
                                'pending' => 'Access queue mein hai; approval/fulfilment complete hone ka wait hai.',
                                default => 'Access status update ka wait hai.',
                            };
                            $progressTone = (float) $progress >= 100 ? 'from-emerald-500 to-teal-500' : ((float) $progress >= 50 ? 'from-blue-700 to-indigo-500' : 'from-orange-500 to-amber-500');
                        @endphp

                        <div class="group rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-xl hover:shadow-blue-900/5 dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-blue-900">
                            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_260px] lg:items-center">
                                <div class="flex min-w-0 gap-4">
                                    <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-3xl bg-gradient-to-br from-blue-50 to-indigo-100 text-xl font-black text-blue-700 ring-1 ring-blue-100 dark:from-blue-950 dark:to-indigo-950 dark:text-blue-200 dark:ring-blue-900">
                                        {{ str($course?->title ?? 'Course')->substr(0, 1)->upper() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="mb-2 flex flex-wrap items-center gap-2">
                                            <x-ui.badge :variant="$statusVariant">{{ str($enrollment->status)->replace('_', ' ') }}</x-ui.badge>
                                            @if($course?->moodle_course_id)
                                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">AC-{{ $course->moodle_course_id }}</span>
                                            @endif
                                            @if($enrollment->access_ends_at)
                                                <x-ui.badge variant="orange">expires {{ $enrollment->access_ends_at->diffForHumans() }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="slate">{{ $course?->accessLabel() ?? 'Access' }}</x-ui.badge>
                                            @endif
                                        </div>
                                        <h3 class="text-xl font-black leading-tight text-slate-950 dark:text-white">{{ $course?->title ?? 'Course' }}</h3>
                                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                            {{ $course?->short_description ?: $statusCopy }}
                                        </p>
                                        <div class="mt-4 grid gap-3 text-xs font-black uppercase tracking-widest text-slate-400 sm:grid-cols-3">
                                            <span>Enrolled: {{ $enrollment->enrolled_at?->format('M d, Y') ?? 'Waiting' }}</span>
                                            <span>Starts: {{ $enrollment->access_starts_at?->format('M d, Y') ?? 'After activation' }}</span>
                                            <span>Access: {{ $enrollment->access_ends_at?->format('M d, Y') ?? 'Lifetime' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                    <div class="flex items-center justify-between text-sm font-black text-slate-700 dark:text-slate-200">
                                        <span>Progress</span>
                                        <span>{{ number_format((float) $progress) }}%</span>
                                    </div>
                                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-white ring-1 ring-slate-100 dark:bg-slate-950 dark:ring-slate-800">
                                        <div class="progress-shine h-full rounded-full bg-gradient-to-r {{ $progressTone }}" style="width: {{ min(100, max(0, (float) $progress)) }}%"></div>
                                    </div>
                                    <p class="mt-3 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ $statusCopy }}</p>

                                    @if($enrollment->last_error)
                                        <p class="mt-3 rounded-2xl bg-red-50 p-3 text-xs font-bold leading-5 text-red-700 dark:bg-red-950/40 dark:text-red-200">
                                            {{ $enrollment->last_error }}
                                        </p>
                                    @endif

                                    @if($enrollment->status === 'active' && $course?->moodle_course_id)
                                        <x-ui.button
                                            href="{{ $academyBaseUrl }}/course/view.php?id={{ $course->moodle_course_id }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="mt-4 w-full px-4 py-2"
                                        >
                                            Continue to ANME Academy
                                            <x-ui.icon name="arrow" class="h-4 w-4" />
                                        </x-ui.button>
                                        <x-ui.button href="{{ route('courses.show', $course) }}#review-course" variant="secondary" class="mt-3 w-full px-4 py-2">
                                            Review course
                                            <x-ui.icon name="star" class="h-4 w-4" />
                                        </x-ui.button>
                                    @elseif($enrollment->status === 'setup_required')
                                        <x-ui.button href="{{ route('academy-access.edit') }}" variant="orange" class="mt-4 w-full px-4 py-2">
                                            Set Academy Login
                                            <x-ui.icon name="arrow" class="h-4 w-4" />
                                        </x-ui.button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="No enrolled courses yet" description="Course purchase aur payment approval ke baad active access yahan show hoga." action="Find a Course" :href="route('courses.index')" />
                    @endforelse
                </div>
            </x-ui.card>

            <aside class="space-y-6">
                <x-ui.card>
                    <div class="flex items-center gap-3">
                        <x-ui.icon-tile name="check" tone="green" size="sm" />
                        <div>
                            <p class="eyebrow">Access Checklist</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Your learning status</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach([
                            ['label' => 'Payment approved', 'ok' => $user->orders->where('status', 'paid')->isNotEmpty(), 'hint' => 'Admin verifies receipt first.'],
                            ['label' => 'Academy login ready', 'ok' => $academyLoginReady, 'hint' => $academyLoginReady ? ($user->academy_username ?? 'Ready') : 'Set login once after first approval.'],
                            ['label' => 'Course access active', 'ok' => $activeEnrollments->isNotEmpty(), 'hint' => $activeEnrollments->count().' active course(s).'],
                            ['label' => 'Progress syncing', 'ok' => $user->progress->isNotEmpty(), 'hint' => $user->progress->isNotEmpty() ? $averageProgress.'% average' : 'Progress appears after sync.'],
                        ] as $item)
                            <div class="flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-xl {{ $item['ok'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-200' : 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-200' }}">
                                    <x-ui.icon :name="$item['ok'] ? 'check' : 'activity'" class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-black text-slate-950 dark:text-white">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ $item['hint'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-ui.icon-tile name="bell" tone="indigo" size="sm" />
                            <div>
                                <p class="eyebrow">Notifications</p>
                                <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">{{ $platformSettings['notification_panel_title'] ?? 'Latest updates' }}</h2>
                            </div>
                        </div>
                        <x-ui.badge variant="indigo">{{ $user->notifications->count() }}</x-ui.badge>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($user->notifications as $notification)
                            <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/30">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-black text-slate-950 dark:text-white">{{ $notification->title }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $notification->message }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs font-black uppercase tracking-widest text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state title="No updates yet" description="Course access, payment and academy updates will appear here." />
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card dark>
                    <p class="text-xs font-black uppercase tracking-widest text-blue-200">Next Best Action</p>
                    <h2 class="mt-2 text-2xl font-black text-white">
                        @if($needsAcademySetup)
                            Activate your Academy login
                        @elseif($activeEnrollments->isNotEmpty())
                            Continue learning today
                        @else
                            Choose your first course
                        @endif
                    </h2>
                    <p class="mt-3 text-sm font-semibold leading-6 text-blue-50/80">
                        @if($needsAcademySetup)
                            Setup complete hotay hi paid courses active ho jayenge.
                        @elseif($activeEnrollments->isNotEmpty())
                            Course open karein, lessons complete karein aur progress yahan sync hoti rahegi.
                        @else
                            Catalog se approved course select karein, payment proof upload karein aur admin approval ke baad access activate ho jayega.
                        @endif
                    </p>
                    <x-ui.button
                        :href="$needsAcademySetup ? route('academy-access.edit') : ($latestActiveEnrollment?->course?->moodle_course_id ? $academyBaseUrl.'/course/view.php?id='.$latestActiveEnrollment->course->moodle_course_id : route('courses.index'))"
                        class="mt-5 w-full"
                        target="{{ (!$needsAcademySetup && $latestActiveEnrollment?->course?->moodle_course_id) ? '_blank' : null }}"
                        rel="{{ (!$needsAcademySetup && $latestActiveEnrollment?->course?->moodle_course_id) ? 'noopener' : null }}"
                    >
                        @if($needsAcademySetup)
                            Set Academy Login
                        @elseif($activeEnrollments->isNotEmpty())
                            Open Latest Course
                        @else
                            Browse Courses
                        @endif
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </x-ui.button>
                </x-ui.card>

                <x-ui.card>
                    <p class="eyebrow">Recent Orders</p>
                    <div class="mt-5 space-y-3">
                        @forelse($recentOrders as $order)
                            @php
                                $orderVariant = match ($order->payment?->status ?? $order->status) {
                                    'paid' => 'green',
                                    'pending_verification' => 'blue',
                                    'rejected', 'failed', 'cancelled' => 'red',
                                    default => 'orange',
                                };
                            @endphp
                            <a href="{{ route('orders.show', $order) }}" class="block rounded-2xl border border-slate-100 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-800 dark:bg-slate-950/30 dark:hover:bg-slate-800/70">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-black text-slate-950 dark:text-white">{{ $order->order_no }}</span>
                                    <x-ui.badge :variant="$orderVariant">{{ str($order->payment?->status ?? $order->status)->replace('_', ' ') }}</x-ui.badge>
                                </div>
                                <p class="mt-1 text-sm font-bold text-slate-500 dark:text-slate-400">{{ $order->currency }} {{ number_format((float) $order->amount) }}</p>
                            </a>
                        @empty
                            <x-ui.empty-state title="No orders yet" description="Your purchases and Easypaisa payment records will appear here." action="Browse Courses" :href="route('courses.index')" />
                        @endforelse
                    </div>
                </x-ui.card>
            </aside>
        </section>
    </div>
</x-app-layout>
