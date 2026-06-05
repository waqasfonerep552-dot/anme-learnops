<x-app-layout>
    {{-- Admin course catalog: academy sync + business pricing/visibility control. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Admin Courses</p>
            <h1 class="admin-page-title">Course catalog management</h1>
            <p class="admin-page-subtitle">Control business pricing, approval and visibility. Students only see courses that admin explicitly allows.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <x-ui.card>
                @if(session('status'))
                    <div class="mb-5 rounded-2xl border border-green-100 bg-green-50 p-4 font-bold text-green-700 dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 p-4 font-bold text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Business catalog</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Prices, featured flags and public approval are managed here; lessons live inside ANME Academy.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.courses.sync') }}">
                            @csrf
                            <x-ui.button type="submit" variant="accent">Sync from Academy</x-ui.button>
                        </form>
                        <x-ui.badge variant="blue">{{ $courses->total() }} courses</x-ui.badge>
                        <x-ui.badge variant="orange">Business pricing</x-ui.badge>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Total synced</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $courseStats['total'] }}</p>
                    </div>
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Public catalog</p>
                        <p class="mt-2 text-3xl font-black {{ $courseStats['public'] > 0 ? 'text-green-700 dark:text-green-300' : 'text-orange-600 dark:text-orange-300' }}">{{ $courseStats['public'] }}</p>
                    </div>
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Needs approval</p>
                        <p class="mt-2 text-3xl font-black text-blue-700 dark:text-blue-300">{{ $courseStats['needs_approval'] }}</p>
                    </div>
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Academy hidden</p>
                        <p class="mt-2 text-3xl font-black text-slate-700 dark:text-slate-200">{{ $courseStats['hidden_in_academy'] }}</p>
                    </div>
                </div>

                @if($courseStats['public'] === 0)
                    <div class="mt-5 rounded-3xl border border-orange-200 bg-orange-50 p-5 shadow-sm dark:border-orange-900/60 dark:bg-orange-950/30">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-start gap-3">
                                <x-ui.icon-tile name="shield" tone="orange" size="sm" />
                                <div>
                                    <h3 class="text-lg font-black text-orange-950 dark:text-orange-100">No public courses are currently available</h3>
                                    <p class="mt-1 text-sm font-semibold leading-6 text-orange-800 dark:text-orange-200">Students will not see checkout courses until at least one course is published, admin approved and visible in ANME Academy.</p>
                                </div>
                            </div>
                            <x-ui.button href="{{ route('admin.courses.index', ['visibility' => 'needs_approval']) }}" variant="orange">Review Approvals</x-ui.button>
                        </div>
                    </div>
                @endif

                @php
                    $visibilityLabels = [
                        'public' => 'Public allowed',
                        'needs_approval' => 'Needs approval',
                        'draft' => 'Draft courses',
                        'archived' => 'Archived courses',
                    ];
                    $hasFilters = collect(['q', 'visibility'])->contains(fn ($key) => filled(request($key)));
                @endphp

                <form class="admin-filter mt-6" method="GET">
                    <label class="filter-search relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search title, code or description...">
                    </label>
                    <select name="visibility" class="admin-input">
                        <option value="">All visibility</option>
                        @foreach($visibilityLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('visibility') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" class="filter-action">Apply Filters</x-ui.button>
                    @if($hasFilters)
                        <x-ui.button :href="route('admin.courses.index')" variant="secondary" class="filter-action">Clear All</x-ui.button>
                    @else
                        <div class="hidden"></div>
                    @endif
                </form>

                @if($hasFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <span>Active filters:</span>
                        @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                        @if(request('visibility')) <x-ui.badge variant="orange">{{ $visibilityLabels[request('visibility')] ?? request('visibility') }}</x-ui.badge> @endif
                    </div>
                @endif

                <div class="admin-table-wrap premium-scroll mt-6">
                    <table class="admin-table admin-table-courses">
                        <thead>
                            <tr>
                                <th class="w-[7%]">ID</th>
                                <th class="w-[34%]">Course</th>
                                <th class="w-[14%]">Category</th>
                                <th class="w-[12%]">Price</th>
                                <th class="w-[15%]">Status</th>
                                <th class="w-[13%]">Academy</th>
                                <th class="w-[8%]">Enrollments</th>
                                <th class="w-[8%] text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                @php
                                    $statusVariant = match ($course->status) {
                                        'published' => 'green',
                                        'draft' => 'orange',
                                        'archived' => 'slate',
                                        default => 'blue',
                                    };
                                @endphp
                                <tr>
                                    <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $course->id }}</td>
                                    <td>
                                        <div class="flex items-start gap-3">
                                            @if($course->thumbnail)
                                                <img src="{{ url('storage/'.ltrim($course->thumbnail, '/')) }}" alt="{{ $course->title }} cover image" class="h-14 w-14 shrink-0 rounded-2xl object-cover shadow-sm ring-1 ring-slate-200 dark:ring-slate-800">
                                            @else
                                                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 dark:bg-indigo-950 dark:text-indigo-200 dark:ring-indigo-900">
                                                    <span class="text-lg font-black">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($course->title, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="cell-title min-w-0">
                                                <div class="font-black leading-5 text-slate-950 dark:text-white">{{ $course->title }}</div>
                                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Course code: AC-{{ $course->moodle_course_id }}</div>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @if($course->is_featured)
                                                        <x-ui.badge variant="orange">Featured</x-ui.badge>
                                                    @endif
                                                    <x-ui.badge :variant="$course->isPubliclyAvailable() ? 'green' : 'slate'">
                                                        {{ $course->isPubliclyAvailable() ? 'Public allowed' : 'Not public' }}
                                                    </x-ui.badge>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-slate-600 dark:text-slate-300">{{ $course->category?->name ?? 'Uncategorized' }}</td>
                                    <td class="cell-compact">
                                        <div class="font-black text-slate-950 dark:text-white">{{ $course->currency }} {{ number_format((float) $course->price) }}</div>
                                        <div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ $course->accessLabel() }}</div>
                                    </td>
                                    <td>
                                        <div class="flex flex-col items-start gap-2">
                                            <x-ui.badge :variant="$statusVariant">{{ $course->status }}</x-ui.badge>
                                            <x-ui.badge :variant="$course->is_admin_approved ? 'green' : 'orange'">
                                                {{ $course->is_admin_approved ? 'Admin approved' : 'Needs approval' }}
                                            </x-ui.badge>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="space-y-1 text-xs font-bold text-slate-500 dark:text-slate-400">
                                            <div>
                                                <x-ui.badge :variant="$course->moodle_visible ? 'green' : 'red'">
                                                    {{ $course->moodle_visible ? 'Visible' : 'Hidden' }}
                                                </x-ui.badge>
                                            </div>
                                            <div>{{ $course->moodle_synced_at ? 'Synced '.$course->moodle_synced_at->diffForHumans() : 'Not synced yet' }}</div>
                                        </div>
                                    </td>
                                    <td class="cell-compact font-black text-blue-700 dark:text-blue-300">{{ $course->enrollments_count }}</td>
                                    <td class="cell-actions">
                                        <div class="admin-actions justify-end">
                                            @if(! $course->isPubliclyAvailable() && $course->status === 'published' && $course->moodle_visible && ! $course->is_admin_approved)
                                                <form method="POST" action="{{ route('admin.courses.approve-public', $course) }}">
                                                    @csrf
                                                    <x-ui.button type="submit" class="min-h-10 px-4 py-2">Approve</x-ui.button>
                                                </form>
                                            @endif
                                            <x-ui.button href="{{ route('admin.courses.edit', $course) }}" variant="secondary" class="min-h-10 px-4 py-2">
                                                <span class="hidden sm:inline">Edit</span>
                                                <x-ui.icon name="arrow" class="h-4 w-4 sm:hidden" />
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <x-ui.empty-state title="No courses found" description="Sync academy courses, then manage their pricing from here." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $courses->links() }}</div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
