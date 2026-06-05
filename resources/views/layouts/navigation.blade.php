@php
    $navItems = [
        ['label' => 'Home', 'route' => 'home', 'active' => request()->routeIs('home')],
        ['label' => 'Courses', 'route' => 'courses.index', 'active' => request()->routeIs('courses.*')],
    ];

    $navNotifications = auth()->check()
        ? auth()->user()->notifications()->latest()->take(5)->get()
        : collect();

    $unreadNotificationCount = auth()->check()
        ? auth()->user()->notifications()->whereNull('read_at')->count()
        : 0;

    $navLinkBase = 'inline-flex h-10 min-w-[5.5rem] shrink-0 items-center justify-center whitespace-nowrap rounded-[0.95rem] px-4 text-sm font-black leading-none transition duration-300';
    $navLinkActive = 'bg-slate-950 text-white shadow-lg shadow-slate-950/15 dark:bg-blue-700 dark:shadow-blue-900/20';
    $navLinkIdle = 'text-slate-600 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800';
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-slate-100/80 bg-white/90 shadow-sm shadow-slate-200/60 backdrop-blur-2xl dark:border-slate-800 dark:bg-slate-950/88 dark:shadow-black/20">
    <div class="app-container">
        <div class="flex min-h-[4.5rem] items-center justify-between gap-4 py-2.5">
            <a href="{{ route('home') }}" class="group flex min-w-0 shrink-0 items-center gap-3">
                <x-application-logo class="h-11 w-11 shrink-0 transition group-hover:scale-105" />
                <span class="min-w-0">
                    <span class="block truncate text-base font-black leading-tight tracking-tight text-slate-950 dark:text-white">{{ $platformSettings['business_name'] ?? 'ANME LearnOps' }}</span>
                    <span class="hidden max-w-56 truncate text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 lg:block">{{ $platformSettings['business_tagline'] ?? 'Training commerce suite' }}</span>
                </span>
            </a>

            <div class="hidden items-center gap-1 rounded-[1.25rem] border border-slate-200/80 bg-white/84 p-1 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80 xl:flex">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="{{ $navLinkBase }} {{ $item['active'] ? $navLinkActive : $navLinkIdle }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @auth
                    <a href="{{ route('dashboard') }}" class="{{ $navLinkBase }} min-w-[7.25rem] {{ request()->routeIs('dashboard') ? $navLinkActive : $navLinkIdle }}">
                        <span class="whitespace-nowrap">My Learning</span>
                    </a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="{{ $navLinkBase }} {{ request()->routeIs('admin.*') ? $navLinkActive : $navLinkIdle }}">Admin</a>
                    @endif
                @endauth
            </div>

            <div class="hidden items-center gap-3 xl:flex">
                <div
                    x-data="{
                        query: @js(request('q') && request()->routeIs('search.index') ? request('q') : ''),
                        results: [],
                        focused: false,
                        loading: false,
                        timer: null,
                        endpoint: @js(route('search.live')),
                        searchUrl: @js(route('search.index')),
                        runSearch() {
                            clearTimeout(this.timer);
                            const value = this.query.trim();
                            if (value.length < 2) {
                                this.results = [];
                                this.loading = false;
                                return;
                            }
                            this.loading = true;
                            this.timer = setTimeout(async () => {
                                try {
                                    const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(value)}`);
                                    const payload = await response.json();
                                    this.results = payload.results || [];
                                } catch (error) {
                                    this.results = [];
                                } finally {
                                    this.loading = false;
                                }
                            }, 220);
                        }
                    }"
                    @click.outside="focused = false"
                    class="relative"
                >
                    <form :action="searchUrl" method="GET" class="relative">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            name="q"
                            x-model="query"
                            @input="focused = true; runSearch()"
                            @focus="focused = true; runSearch()"
                            class="h-10 w-60 rounded-[1.05rem] border border-slate-200 bg-white/88 pl-10 pr-4 text-sm font-bold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:w-72 focus:border-blue-300 focus:ring-4 focus:ring-blue-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:focus:border-blue-700 dark:focus:ring-blue-950 2xl:w-72"
                            placeholder="Search courses, orders..."
                            autocomplete="off"
                        >
                    </form>

                    <div
                        x-cloak
                        x-show="focused && query.trim().length >= 2"
                        x-transition
                        class="absolute right-0 top-14 z-50 w-[24rem] overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-2xl shadow-slate-900/15 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div class="border-b border-slate-100 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-400 dark:border-slate-800">
                            Live Search
                        </div>
                        <div class="max-h-96 overflow-y-auto p-2">
                            <template x-if="loading">
                                <div class="px-4 py-5 text-sm font-bold text-slate-500 dark:text-slate-400">Searching...</div>
                            </template>

                            <template x-if="!loading && results.length === 0">
                                <div class="px-4 py-5 text-sm font-bold text-slate-500 dark:text-slate-400">No quick results. Press Enter for full search.</div>
                            </template>

                            <template x-for="item in results" :key="`${item.type}-${item.url}`">
                                <a :href="item.url" class="flex items-start gap-3 rounded-2xl px-3 py-3 transition hover:bg-blue-50 dark:hover:bg-slate-900">
                                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                                        <x-ui.icon name="search" class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-black text-slate-950 dark:text-white" x-text="item.title"></span>
                                        <span class="mt-1 block truncate text-xs font-semibold text-slate-500 dark:text-slate-400" x-text="item.subtitle"></span>
                                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:bg-slate-800 dark:text-slate-300" x-text="item.type"></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                        <a :href="`${searchUrl}?q=${encodeURIComponent(query)}`" class="block border-t border-slate-100 px-4 py-3 text-center text-xs font-black uppercase tracking-widest text-blue-700 transition hover:bg-blue-50 dark:border-slate-800 dark:text-blue-300 dark:hover:bg-slate-900">
                            Open full search
                        </a>
                    </div>
                </div>

                @auth
                    <x-dropdown align="right" width="80">
                        <x-slot name="trigger">
                            <button class="premium-icon-button group h-10 w-10 overflow-visible rounded-[1.05rem]" aria-label="Open notifications">
                                <x-ui.icon name="bell" class="h-5 w-5" />
                                @if($unreadNotificationCount > 0)
                                    <span class="pointer-events-none absolute right-0 top-0 grid h-5 min-w-5 translate-x-1/3 -translate-y-1/3 place-items-center rounded-full bg-orange-500 px-1.5 text-[10px] font-black leading-none text-white shadow-lg shadow-orange-500/30 ring-2 ring-white dark:ring-slate-950">
                                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-80 p-2">
                                <div class="px-3 py-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Notifications</p>
                                    <h3 class="mt-1 text-base font-black text-slate-950 dark:text-white">Latest platform updates</h3>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    @forelse($navNotifications as $notification)
                                        <a href="{{ data_get($notification->data, 'url', route('dashboard')) }}" class="block rounded-2xl px-3 py-3 transition hover:bg-blue-50 dark:hover:bg-slate-800">
                                            <div class="flex items-start gap-3">
                                                <span class="mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                                                    <x-ui.icon name="check" class="h-4 w-4" />
                                                </span>
                                                <span>
                                                    <span class="block text-sm font-black text-slate-900 dark:text-white">{{ $notification->title }}</span>
                                                    <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</span>
                                                    <span class="mt-1 block text-[11px] font-black uppercase tracking-widest text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                                </span>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="rounded-2xl px-3 py-6 text-center">
                                            <p class="text-sm font-black text-slate-800 dark:text-slate-100">No notifications yet</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Course access, payment and academy updates will appear here.</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="border-t border-slate-100 px-3 pt-3 dark:border-slate-800">
                                    <a href="{{ route('dashboard') }}" class="block rounded-xl bg-slate-950 px-4 py-2.5 text-center text-xs font-black uppercase tracking-widest text-white transition hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">
                                        View dashboard
                                    </a>
                                </div>
                            </div>
                        </x-slot>
                    </x-dropdown>

                    <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="premium-icon-button group h-10 w-10 rounded-[1.05rem]" aria-label="Toggle dark mode">
                        <x-ui.icon name="moon" class="h-5 w-5" />
                    </button>
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="group flex h-11 max-w-72 items-center gap-3 rounded-[1.15rem] border border-slate-200 bg-white px-3 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-black text-white shadow-lg shadow-blue-900/20">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                                <span class="hidden min-w-0 text-left md:block">
                                    <span class="block max-w-40 truncate text-sm font-black leading-tight text-slate-900 dark:text-white">{{ Auth::user()->name }}</span>
                                    <span class="block max-w-40 truncate text-xs font-semibold text-slate-400">{{ Auth::user()->email }}</span>
                                </span>
                                <x-ui.icon name="chevron-down" class="h-4 w-4 text-slate-400 transition group-hover:translate-y-0.5 group-hover:text-blue-600" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('dashboard')">My Learning</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">Profile Settings</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <x-ui.button :href="route('login')" variant="secondary" class="py-2.5">Login</x-ui.button>
                    <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="premium-icon-button group" aria-label="Toggle dark mode">
                        <x-ui.icon name="moon" class="h-5 w-5" />
                    </button>
                    <x-ui.button :href="route('register')" class="py-2.5">Create Account</x-ui.button>
                @endauth
            </div>

            <div class="flex items-center gap-2 xl:hidden">
                <a href="{{ route('search.index') }}" class="premium-icon-button group {{ request()->routeIs('search.index') ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/60 dark:text-blue-200' : '' }}" aria-label="Open global search" title="Global Search">
                    <x-ui.icon name="search" class="h-5 w-5" />
                </a>
                <button @click="open = ! open" class="premium-icon-button group" aria-label="Open mobile menu">
                    <span :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex">
                        <x-ui.icon name="menu" class="h-6 w-6" />
                    </span>
                    <span :class="{'hidden': ! open, 'inline-flex': open }" class="hidden">
                        <x-ui.icon name="x" class="h-6 w-6" />
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 bg-white/95 dark:border-slate-800 dark:bg-slate-950/95 xl:hidden">
        <div class="app-container grid gap-2 py-4">
            @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}" class="rounded-2xl px-4 py-3 text-sm font-black {{ $item['active'] ? 'bg-slate-950 text-white dark:bg-blue-700' : 'text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800' }}">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('search.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black {{ request()->routeIs('search.index') ? 'bg-slate-950 text-white dark:bg-blue-700' : 'text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                <x-ui.icon name="search" class="h-5 w-5" />
                Global Search
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">My Learning</a>
                <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">
                    <span>Notifications</span>
                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-black text-orange-700 dark:bg-orange-950 dark:text-orange-200">{{ $unreadNotificationCount }}</span>
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">Admin</a>
                @endif
                <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="rounded-2xl px-4 py-3 text-left text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">Toggle Dark Mode</button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-2xl px-4 py-3 text-left text-sm font-black text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40">Log Out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-2xl px-4 py-3 text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">Login</a>
                <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="rounded-2xl px-4 py-3 text-left text-sm font-black text-slate-700 hover:bg-blue-50 dark:text-slate-200 dark:hover:bg-slate-800">Toggle Dark Mode</button>
                <a href="{{ route('register') }}" class="rounded-2xl bg-blue-700 px-4 py-3 text-sm font-black text-white">Create Account</a>
            @endauth
        </div>
    </div>
</nav>
