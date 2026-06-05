<x-app-layout>
    {{-- Admin students: website student + academy user ID mapping monitor karne ke liye. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Admin Students</p>
            <h1 class="admin-page-title">Student records</h1>
            <p class="admin-page-subtitle">A clean business-side view of students, academy IDs, orders and active enrolments.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <x-ui.card>
                @php
                    $accessLabels = [
                        'active' => 'Active access',
                        'pending' => 'Pending access',
                        'setup_required' => 'Setup required',
                        'failed' => 'Failed access',
                        'suspended' => 'Suspended access',
                        'expired' => 'Expired access',
                        'none' => 'No access records',
                    ];
                    $hasFilters = collect(['q', 'access_status'])->contains(fn ($key) => filled(request($key)));
                @endphp

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950 dark:text-white">Learner directory</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Find learners by identity, academy link, orders, enrolments or login source.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($hasFilters)
                            <x-ui.button href="{{ route('admin.students.index') }}" variant="secondary">Clear All</x-ui.button>
                        @endif
                        <x-ui.badge variant="indigo">{{ $students->total() }} total</x-ui.badge>
                    </div>
                </div>

                <form class="admin-filter mt-6" method="GET">
                    <label class="filter-search relative block">
                        <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                        <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search name, email, phone, academy ID...">
                    </label>
                    <select name="access_status" class="admin-input">
                        <option value="">Access: all</option>
                        @foreach($accessLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('access_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" class="filter-action">Apply Filters</x-ui.button>
                </form>

                @if($hasFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <span>Active filters:</span>
                        @if(request('q')) <x-ui.badge variant="blue">Search: {{ request('q') }}</x-ui.badge> @endif
                        @if(request('access_status')) <x-ui.badge variant="green">{{ $accessLabels[request('access_status')] ?? request('access_status') }}</x-ui.badge> @endif
                    </div>
                @endif

                <div class="admin-table-wrap premium-scroll mt-6">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Phone</th>
                                <th>Academy</th>
                                <th>Orders</th>
                                <th>Enrollments</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $student->id }}</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-900">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-black text-slate-950 dark:text-white">{{ $student->name }}</div>
                                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $student->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(filled($student->phone))
                                            <div class="font-bold text-slate-700 dark:text-slate-200">{{ $student->phone }}</div>
                                        @else
                                            <x-ui.badge variant="orange">Missing phone</x-ui.badge>
                                            <div class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Checkout/profile se add kar sakte hain.</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->moodle_user_id)
                                            <x-ui.badge variant="green">ID {{ $student->moodle_user_id }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="orange">Pending</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="font-black text-slate-950 dark:text-white">{{ $student->orders_count }}</td>
                                    <td class="font-black text-blue-700 dark:text-blue-300">{{ $student->enrollments_count }}</td>
                                    <td class="text-slate-500 dark:text-slate-400">{{ $student->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <x-ui.empty-state title="No students found" description="Students will appear here after registration or successful checkout." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">{{ $students->links() }}</div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
