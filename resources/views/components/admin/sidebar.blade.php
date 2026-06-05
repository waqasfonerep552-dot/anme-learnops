@php
    $links = [
        ['label' => 'Overview', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Orders', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'icon' => 'orders'],
        ['label' => 'Students', 'route' => 'admin.students.index', 'match' => 'admin.students.*', 'icon' => 'students'],
        ['label' => 'Courses', 'route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'courses'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'icon' => 'reports'],
        ['label' => 'Reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'icon' => 'star'],
        ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'match' => 'admin.notifications.*', 'icon' => 'bell'],
        ['label' => 'Activity Logs', 'route' => 'admin.activity.index', 'match' => 'admin.activity.*', 'icon' => 'activity'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'match' => 'admin.settings.*', 'icon' => 'settings'],
    ];
@endphp

<aside {{ $attributes->merge(['class' => 'admin-dasher-sidebar premium-surface p-4 xl:sticky xl:top-28 xl:h-fit']) }}>
    <div class="admin-sidebar-identity premium-dark-surface p-5">
        <div class="relative z-10">
            <div class="flex items-center justify-between gap-3">
                <x-ui.icon-tile name="shield" tone="blue" />
                <span class="rounded-full bg-green-400/15 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-green-300">Live</span>
            </div>
            <p class="mt-5 text-xs font-black uppercase tracking-widest text-blue-200">Admin Workspace</p>
            <h2 class="mt-2 text-xl font-black text-white">Operations Center</h2>
            <p class="mt-2 text-sm leading-6 text-slate-300">Payments, courses, students, reports and academy fulfilment.</p>
        </div>
    </div>

    <div class="mt-5 flex items-center justify-between px-2">
        <span class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-400">Navigation</span>
        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50"></span>
    </div>

    <nav class="admin-sidebar-nav mt-3 grid gap-1.5">
        @foreach($links as $link)
            @php($active = request()->routeIs($link['match']))
            <a href="{{ route($link['route']) }}" class="admin-sidebar-link group flex items-center justify-between rounded-2xl px-3 py-3 text-sm font-bold transition {{ $active ? 'admin-sidebar-link-active bg-blue-700 text-white shadow-lg shadow-blue-900/20' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="premium-nav-icon {{ $active ? 'premium-nav-icon-active' : '' }}">
                        <x-ui.icon :name="$link['icon']" class="h-[1.1rem] w-[1.1rem]" />
                    </span>
                    <span class="truncate">{{ $link['label'] }}</span>
                </span>
                <x-ui.icon name="arrow" class="h-4 w-4 shrink-0 transition group-hover:translate-x-0.5" />
            </a>
        @endforeach
    </nav>
</aside>
