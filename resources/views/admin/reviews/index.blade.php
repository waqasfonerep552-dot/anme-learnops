<x-app-layout>
    {{-- Admin reviews: student feedback ko public display se pehle moderate karne ke liye. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Admin Reviews</p>
            <h1 class="admin-page-title">Review moderation</h1>
            <p class="admin-page-subtitle">Approve high-quality learner feedback, reject noisy reviews and feature strong testimonials.</p>
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

                @php
                    $statusLabels = [
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ];
                    $hasFilters = collect(['q', 'status'])->contains(fn ($key) => filled(request($key)));
                @endphp

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Learner feedback queue</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Public course pages only show approved reviews.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($hasFilters)
                            <x-ui.button href="{{ route('admin.reviews.index') }}" variant="secondary">Clear All</x-ui.button>
                        @endif
                        <x-ui.badge variant="orange">{{ $reviews->total() }} reviews</x-ui.badge>
                    </div>
                </div>

                <form class="admin-filter mt-6" method="GET">
                    <label class="filter-search relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search review, course, student...">
                    </label>
                    <select name="status" class="admin-input">
                        <option value="">All statuses</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" class="filter-action">Apply Filters</x-ui.button>
                </form>

                @if($hasFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <span>Active filters:</span>
                        @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                        @if(request('status')) <x-ui.badge variant="slate">{{ $statusLabels[request('status')] ?? request('status') }}</x-ui.badge> @endif
                    </div>
                @endif

                <div class="mt-6 grid gap-4">
                    @forelse($reviews as $review)
                        @php
                            $statusVariant = match ($review->status) {
                                'approved' => 'green',
                                'rejected' => 'red',
                                default => 'orange',
                            };
                        @endphp
                        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="grid gap-5 xl:grid-cols-[1fr_320px]">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge :variant="$statusVariant">{{ $review->status }}</x-ui.badge>
                                        @if($review->is_featured)
                                            <x-ui.badge variant="orange">Featured</x-ui.badge>
                                        @endif
                                        <span class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>

                                    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 class="text-xl font-black text-slate-950 dark:text-white">{{ $review->title ?: 'Untitled review' }}</h3>
                                            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
                                                {{ $review->user?->name ?? 'Student' }} &middot; {{ $review->user?->email ?? 'No email' }}
                                            </p>
                                            <p class="mt-1 text-sm font-semibold text-blue-700 dark:text-blue-300">{{ $review->course?->title ?? 'Deleted course' }}</p>
                                        </div>
                                        <div class="flex gap-1 text-orange-500">
                                            @for($star = 1; $star <= 5; $star++)
                                                <span class="text-lg leading-none {{ $star <= $review->rating ? '' : 'opacity-25' }}">★</span>
                                            @endfor
                                        </div>
                                    </div>

                                    <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $review->body }}</p>

                                    @if($review->approvedBy)
                                        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-400">
                                            Approved by {{ $review->approvedBy->name }} {{ $review->approved_at?->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>

                                <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="grid gap-3">
                                        @csrf
                                        @method('PATCH')
                                        <label>
                                            <span class="mb-2 block text-xs font-black uppercase tracking-widest text-slate-400">Moderation status</span>
                                            <select name="status" class="admin-input">
                                                @foreach($statusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($review->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                                            <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-orange-600" @checked($review->is_featured)>
                                            Feature this testimonial
                                        </label>
                                        <x-ui.button type="submit">Save Moderation</x-ui.button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="mt-3" onsubmit="return confirm('Delete this review permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" class="w-full">Delete Review</x-ui.button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <x-ui.empty-state title="No reviews found" description="Student reviews will appear here after paid/enrolled learners submit feedback." />
                    @endforelse
                </div>

                <div class="mt-6">{{ $reviews->links() }}</div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
