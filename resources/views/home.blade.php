<x-app-layout>
    {{-- Public landing page: visitor ko trust, courses aur purchase-to-access flow clear dikhata hai. --}}
    <section class="relative overflow-hidden">
        <div class="app-container py-10 lg:py-16">
            <div class="grid gap-8 xl:grid-cols-[1.05fr_.95fr] xl:items-center">
                <div class="page-hero">
                    <div class="relative z-10">
                        <div class="glass-chip text-blue-700 dark:text-blue-200">
                            <span class="h-2 w-2 rounded-full bg-green-500 shadow-lg shadow-green-500/40"></span>
                            Training commerce + ANME Academy automation
                        </div>

                        <h1 class="hero-title mt-7">
                            Build a premium training business.
                            <span class="premium-gradient-text">Deliver courses through ANME Academy.</span>
                        </h1>

                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                            A polished learning storefront where students discover courses, pay through Easypaisa, receive access updates, and continue learning inside ANME Academy after secure enrolment.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <x-ui.button :href="route('courses.index')" class="px-6 py-3.5">Explore Courses <x-ui.icon name="arrow" class="h-4 w-4" /></x-ui.button>
                            <x-ui.button :href="route('login')" variant="secondary" class="px-6 py-3.5">Student Login</x-ui.button>
                        </div>

                        <div class="mt-10 grid gap-3 sm:grid-cols-3">
                            @foreach([
                                ['Fast access', 'Paid orders queue enrolment automatically'],
                                ['Admin control', 'Courses, reports and notifications'],
                                ['Secure flow', 'No course access before payment'],
                            ] as $metric)
                                <div class="metric-card">
                                    <p class="text-sm font-black text-slate-950 dark:text-white">{{ $metric[0] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $metric[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="premium-dark-surface p-5 sm:p-7">
                    <div class="relative z-10">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-blue-200">Live Operations Preview</p>
                                <h2 class="mt-2 text-2xl font-black">Payment to classroom</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-300">A business-safe flow that keeps learning delivery connected but controlled.</p>
                            </div>
                            <span class="rounded-2xl bg-green-400/15 px-3 py-2 text-xs font-black uppercase tracking-widest text-green-300">Online</span>
                        </div>

                        <div class="mt-7 rounded-[1.5rem] border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <div class="grid gap-3">
                                @foreach([
                                    ['01', 'Course selected', 'Student chooses from the public catalog'],
                                    ['02', 'Payment recorded', 'Order waits for Easypaisa confirmation'],
                                ['03', 'Academy access opens', 'System prepares the student account and enrols the user'],
                                    ['04', 'Dashboard updated', 'Notifications, progress and orders become visible'],
                                ] as $step)
                                    <div class="grid gap-3 rounded-2xl border border-white/10 bg-slate-950/40 p-4 sm:grid-cols-[3rem_1fr_auto] sm:items-center">
                                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-sm font-black text-slate-950">{{ $step[0] }}</span>
                                        <span>
                                            <span class="block font-black text-white">{{ $step[1] }}</span>
                                            <span class="mt-1 block text-sm text-blue-100">{{ $step[2] }}</span>
                                        </span>
                                        <span class="hidden h-2 w-2 rounded-full bg-green-400 sm:block"></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            @foreach([[$featuredCourses->count(), 'Featured'], [$categories->count(), 'Tracks'], ['PKR', 'Payments']] as $item)
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 text-center">
                                    <p class="text-2xl font-black text-white">{{ $item[0] }}</p>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-widest text-blue-200">{{ $item[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="app-container py-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Academy Storefront', 'Show courses, pricing and student journeys under the ANME Academy brand.', 'academy', 'blue'],
                ['Learning Engine', 'Course content, completion and progress stay connected through the ANME Academy learning system.', 'courses', 'indigo'],
                ['Role Control', 'Separate admin, instructor and student experiences with protected access.', 'shield', 'green'],
                ['Operations Ready', 'Notifications, diagnostics and fulfilment tracking keep access reliable.', 'activity', 'orange'],
            ] as $card)
                <x-ui.card class="lift-card">
                    <x-ui.icon-tile :name="$card[2]" :tone="$card[3]" class="mb-5" />
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">{{ $card[0] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $card[1] }}</p>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    <section class="app-container py-10">
        <div class="mb-7 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Categories</p>
                <h2 class="section-title mt-2">Explore training tracks</h2>
                <p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-300">Browse live categories synced from the learning system and curated for the business catalog.</p>
            </div>
            <x-ui.button :href="route('courses.index')" variant="secondary">Browse All</x-ui.button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @forelse($categories->take(4) as $category)
                <a href="{{ route('courses.index', ['category' => $category->slug]) }}" class="premium-surface group block p-5 transition hover:-translate-y-1 hover:shadow-blue-200/70 dark:hover:shadow-slate-950/70">
                    <div class="flex items-start justify-between gap-4">
                        <x-ui.icon-tile name="courses" tone="indigo" />
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-300">{{ $category->courses_count }} courses</span>
                    </div>
                    <h3 class="mt-5 text-xl font-black text-slate-950 dark:text-white">{{ $category->name }}</h3>
                    <p class="mt-2 inline-flex items-center gap-2 text-sm text-slate-500 transition group-hover:translate-x-1 dark:text-slate-400">Open track <x-ui.icon name="arrow" class="h-4 w-4" /></p>
                </a>
            @empty
                <x-ui.empty-state class="md:col-span-2 xl:col-span-4" title="No categories yet" description="Sync courses to build category cards automatically." />
            @endforelse
        </div>
    </section>

    <section class="app-container py-12">
        <div class="mb-7 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Featured Courses</p>
                <h2 class="section-title mt-2">Popular courses ready for checkout</h2>
                <p class="mt-3 max-w-2xl text-slate-600 dark:text-slate-300">Featured courses are mapped to academy course records and priced for the public storefront.</p>
            </div>
            <x-ui.button :href="route('courses.index')" variant="secondary">View Full Catalog</x-ui.button>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($featuredCourses as $course)
                <x-course.card :course="$course" />
            @empty
                <x-ui.empty-state class="md:col-span-2 xl:col-span-3" title="No featured courses yet" description="Mark courses as featured from the admin course screen." />
            @endforelse
        </div>
    </section>

    <section class="app-container pb-16">
        <div class="premium-dark-surface grid gap-6 p-8 lg:grid-cols-[1fr_auto] lg:items-center">
            <div class="relative z-10">
                <p class="text-xs font-black uppercase tracking-widest text-blue-200">Ready for review</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white md:text-4xl">A clean business layer for ANME Academy learning.</h2>
                <p class="mt-3 max-w-3xl text-slate-300">Students get a modern purchase and dashboard experience; admins get control over courses, reports, payments, notifications and fulfilment.</p>
            </div>
            <x-ui.button :href="route('courses.index')" variant="orange" class="relative z-10">Explore Courses</x-ui.button>
        </div>
    </section>
</x-app-layout>
