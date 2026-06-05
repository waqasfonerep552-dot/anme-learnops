<x-app-layout>
    {{-- Global search: student courses dhoondta hai, admin business records bhi quickly trace karta hai. --}}
    <x-slot name="header">
        <div class="page-hero">
            <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="eyebrow">Global Search</p>
                    <h1 class="admin-page-title mt-2">Find anything faster</h1>
                    <p class="admin-page-subtitle">Search courses, categories and training access information from one premium command-style screen.</p>
                </div>
                <div class="metric-card min-w-40 text-center">
                    <p class="text-2xl font-black text-slate-950 dark:text-white">{{ $courses->count() + $categories->count() + $orders->count() + $students->count() }}</p>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Results</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container">
            <x-ui.card>
                <form method="GET" action="{{ route('search.index') }}" class="grid gap-3 lg:grid-cols-[1fr_auto]">
                    <label class="relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-5 top-1/2 h-6 w-6 -translate-y-1/2 text-blue-500" />
                        <input
                            name="q"
                            value="{{ $search }}"
                            class="admin-input min-h-14 rounded-[1.4rem] pl-14 text-base"
                            placeholder="Search courses, categories, order number, student email..."
                            autofocus
                        >
                    </label>
                    <x-ui.button type="submit" class="min-h-14 px-8">
                        Search
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </x-ui.button>
                </form>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-blue-700 dark:bg-blue-950 dark:text-blue-200">Courses</span>
                    <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200">Categories</span>
                    @if($isAdmin)
                        <span class="rounded-full bg-orange-50 px-3 py-1.5 text-orange-700 dark:bg-orange-950 dark:text-orange-200">Orders</span>
                        <span class="rounded-full bg-green-50 px-3 py-1.5 text-green-700 dark:bg-green-950 dark:text-green-200">Students</span>
                    @endif
                </div>
            </x-ui.card>

            @if($search === '')
                <div class="mt-8">
                    <x-ui.empty-state
                        title="Start typing to search"
                        description="Use the global search icon from the navbar whenever you need quick access to courses, categories or business records."
                    />
                </div>
            @else
                <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_24rem]">
                    <div class="space-y-6">
                        <x-ui.card>
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="eyebrow">Course Results</p>
                                    <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Matching courses</h2>
                                </div>
                                <x-ui.badge variant="blue">{{ $courses->count() }}</x-ui.badge>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                @forelse($courses as $course)
                                    <x-course.card :course="$course" />
                                @empty
                                    <x-ui.empty-state class="md:col-span-2" title="No matching courses" description="Try another keyword or browse the full course catalog." action="Browse courses" :href="route('courses.index')" />
                                @endforelse
                            </div>
                        </x-ui.card>

                        @if($isAdmin)
                            <x-ui.card>
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="eyebrow">Admin Quick Results</p>
                                        <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Orders and students</h2>
                                    </div>
                                    <x-ui.badge variant="orange">{{ $orders->count() + $students->count() }}</x-ui.badge>
                                </div>

                                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                        <h3 class="font-black text-slate-950 dark:text-white">Orders</h3>
                                        <div class="mt-4 space-y-3">
                                            @forelse($orders as $order)
                                                @php
                                                    $orderVariant = match ($order->payment?->status ?? $order->status) {
                                                        'paid' => 'green',
                                                        'pending_verification' => 'blue',
                                                        'rejected', 'failed', 'cancelled' => 'red',
                                                        default => 'orange',
                                                    };
                                                @endphp
                                                <a href="{{ route('orders.show', $order) }}" class="block rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="font-black text-blue-700 dark:text-blue-300">{{ $order->order_no }}</span>
                                                        <x-ui.badge :variant="$orderVariant">{{ $order->payment?->status ?? $order->status }}</x-ui.badge>
                                                    </div>
                                                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $order->user?->name }} &middot; {{ $order->currency }} {{ number_format((float) $order->amount) }}</p>
                                                </a>
                                            @empty
                                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No matching orders.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                        <h3 class="font-black text-slate-950 dark:text-white">Students</h3>
                                        <div class="mt-4 space-y-3">
                                            @forelse($students as $student)
                                                <a href="{{ route('admin.students.index', ['q' => $student->email]) }}" class="block rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900">
                                                    <div class="flex items-center gap-3">
                                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                                                        </span>
                                                        <span>
                                                            <span class="block font-black text-slate-950 dark:text-white">{{ $student->name }}</span>
                                                            <span class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $student->email }}</span>
                                                        </span>
                                                    </div>
                                                </a>
                                            @empty
                                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No matching students.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </x-ui.card>
                        @endif
                    </div>

                    <aside class="space-y-6">
                        <x-ui.card>
                            <p class="eyebrow">Categories</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Matching categories</h2>
                            <div class="mt-5 space-y-3">
                                @forelse($categories as $category)
                                    <a href="{{ route('courses.index', ['category' => $category->slug]) }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 font-black text-slate-800 transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
                                        <span>{{ $category->name }}</span>
                                        <span class="text-xs uppercase tracking-widest text-slate-400">{{ $category->courses_count }} courses</span>
                                    </a>
                                @empty
                                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No matching categories.</p>
                                @endforelse
                            </div>
                        </x-ui.card>

                        <x-ui.card class="bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 text-white dark:from-slate-900 dark:via-blue-950 dark:to-indigo-950">
                            <x-ui.icon-tile name="search" tone="orange" />
                            <h2 class="mt-5 text-2xl font-black">Search tip</h2>
                            <p class="mt-2 text-sm leading-6 text-blue-100">Try course title, skill name, instructor, category, order number, student name or email.</p>
                        </x-ui.card>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
