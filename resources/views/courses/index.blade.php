<x-app-layout>
    {{-- Course catalog: search/category filters se student sellable business courses browse karta hai. --}}
    <x-slot name="header">
        <div class="page-hero">
            <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="eyebrow">Course Store</p>
                    <h1 class="admin-page-title mt-2">Find the right training course</h1>
                    <p class="admin-page-subtitle">Search live catalog courses, choose a training path, and get ANME Academy access after payment verification.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center">
                    <div class="metric-card text-center">
                        <p class="text-2xl font-black text-slate-950 dark:text-white">{{ $courses->total() }}</p>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Courses</p>
                    </div>
                    <div class="metric-card text-center">
                        <p class="text-2xl font-black text-slate-950 dark:text-white">{{ $categories->count() }}</p>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Categories</p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container">
            <x-ui.card>
                @php
                    $hasFilters = collect(['q', 'category', 'level', 'min_price', 'max_price', 'featured', 'sort'])->contains(fn ($key) => filled(request($key)) && request($key) !== 'latest');
                    $activeCategory = $categories->firstWhere('slug', request('category'))?->name;
                    $sortLabels = [
                        'latest' => 'Newest first',
                        'featured' => 'Featured first',
                        'price_low' => 'Price: low to high',
                        'price_high' => 'Price: high to low',
                        'title' => 'Title A-Z',
                    ];
                @endphp

                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="eyebrow">Smart Filters</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Refine your course search</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Filter by category, level, budget and featured courses.</p>
                    </div>
                    @if($hasFilters)
                        <x-ui.button :href="route('courses.index')" variant="secondary">Clear All</x-ui.button>
                    @endif
                </div>

                <form class="admin-filter mt-6" method="GET">
                    <label class="filter-search relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search courses, instructor, skills...">
                    </label>
                    <select name="category" class="admin-input">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select name="level" class="admin-input">
                        <option value="">All levels</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    <input name="min_price" type="number" min="0" value="{{ request('min_price') }}" class="admin-input" placeholder="Min PKR">
                    <input name="max_price" type="number" min="0" value="{{ request('max_price') }}" class="admin-input" placeholder="Max PKR">
                    <select name="sort" class="admin-input">
                        @foreach($sortLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('sort', 'latest') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <label class="flex min-h-11 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 text-sm font-black text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            <input type="checkbox" name="featured" value="1" class="rounded border-slate-300 text-blue-700" @checked(request()->boolean('featured'))>
                            Featured
                        </label>
                        <x-ui.button type="submit" class="filter-action px-5">Apply</x-ui.button>
                    </div>
                </form>

                @if($hasFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <span>Active filters:</span>
                        @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                        @if($activeCategory) <x-ui.badge variant="orange">{{ $activeCategory }}</x-ui.badge> @endif
                        @if(request('level')) <x-ui.badge variant="indigo">{{ request('level') }}</x-ui.badge> @endif
                        @if(request('min_price')) <x-ui.badge variant="slate">Min {{ request('min_price') }}</x-ui.badge> @endif
                        @if(request('max_price')) <x-ui.badge variant="slate">Max {{ request('max_price') }}</x-ui.badge> @endif
                        @if(request()->boolean('featured')) <x-ui.badge variant="orange">Featured only</x-ui.badge> @endif
                        @if(request('sort') && request('sort') !== 'latest') <x-ui.badge variant="green">{{ $sortLabels[request('sort')] ?? request('sort') }}</x-ui.badge> @endif
                    </div>
                @endif
            </x-ui.card>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse($courses as $course)
                    <x-course.card :course="$course" />
                @empty
                    <x-ui.empty-state class="md:col-span-2 xl:col-span-3" title="No courses found" description="Try another search keyword or category." action="Reset filters" :href="route('courses.index')" />
                @endforelse
            </div>

            <div class="mt-8">
                {{ $courses->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
