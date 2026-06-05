<x-app-layout>
    {{-- Admin orders: Easypaisa payment status aur academy fulfilment trail check karne ke liye. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Admin Orders</p>
            <h1 class="admin-page-title">Order management</h1>
            <p class="admin-page-subtitle">Track Easypaisa payments, order health and academy fulfilment from one operations screen.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="space-y-6">
                @if(session('status'))
                    <div class="rounded-3xl border border-green-100 bg-green-50 p-5 font-bold text-green-700 shadow-sm dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-3xl border border-red-100 bg-red-50 p-5 font-bold text-red-700 shadow-sm dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="mini-stat">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Visible Orders</p>
                            <x-ui.icon-tile name="orders" tone="blue" size="sm" />
                        </div>
                        <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $orders->count() }}</p>
                    </div>
                    <div class="mini-stat">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Total Records</p>
                            <x-ui.icon-tile name="reports" tone="indigo" size="sm" />
                        </div>
                        <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $orders->total() }}</p>
                    </div>
                    <div class="mini-stat">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Active Filter</p>
                            <x-ui.icon-tile name="search" tone="orange" size="sm" />
                        </div>
                        <p class="mt-3 text-2xl font-black text-blue-700 dark:text-blue-300">{{ request('status') || request('q') ? 'On' : 'All' }}</p>
                    </div>
                </div>

                <x-ui.card>
                    @php
                        $statusLabels = [
                            'pending' => 'Pending',
                            'pending_verification' => 'Pending verification',
                            'paid' => 'Paid',
                            'rejected' => 'Rejected',
                            'failed' => 'Failed',
                            'cancelled' => 'Cancelled',
                        ];
                        $hasFilters = collect(['q', 'status'])->contains(fn ($key) => filled(request($key)));
                    @endphp

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-950 dark:text-white">Orders pipeline</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Search by order number, student, payment status, date or fulfilment health.</p>
                        </div>
                        @if($hasFilters)
                            <x-ui.button href="{{ route('admin.orders.index') }}" variant="secondary">Clear All</x-ui.button>
                        @endif
                    </div>

                    <form class="admin-filter mt-6" method="GET">
                        <label class="filter-search relative block">
                            <x-ui.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                            <input name="q" value="{{ request('q') }}" class="admin-input pl-12" placeholder="Search order, student, email or payment ref...">
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

                    <div class="admin-table-wrap premium-scroll mt-6">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Order</th>
                                    <th>Student</th>
                                    <th>Courses</th>
                                    <th>Payment</th>
                                    <th>Fulfilment</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php
                                        $paymentStatus = $order->payment?->status ?? $order->status;
                                        $statusVariant = match ($paymentStatus) {
                                            'paid' => 'green',
                                            'failed', 'cancelled', 'rejected' => 'red',
                                            'pending_verification' => 'blue',
                                            'pending' => 'orange',
                                            default => 'slate',
                                        };
                                        $isPaid = $paymentStatus === 'paid' || $order->status === 'paid';
                                        $hasFailedFulfilment = $order->enrollments->contains('status', 'failed');
                                        $hasActiveFulfilment = $order->enrollments->contains('status', 'active');
                                        $hasSuspendedFulfilment = $order->enrollments->contains('status', 'suspended');
                                        $hasSetupRequired = $order->enrollments->contains('status', 'setup_required');
                                        $hasAnyFulfilment = $order->enrollments->isNotEmpty();
                                        $fulfilmentText = $hasFailedFulfilment
                                            ? 'failed'
                                            : ($hasSetupRequired ? 'setup required' : ($hasSuspendedFulfilment ? 'suspended' : ($hasActiveFulfilment ? 'active' : ($hasAnyFulfilment ? 'pending' : ($isPaid ? 'not queued' : 'waiting payment')))));
                                        $fulfilmentVariant = match ($fulfilmentText) {
                                            'active' => 'green',
                                            'failed' => 'red',
                                            'setup required' => 'blue',
                                            'suspended' => 'orange',
                                            'pending', 'not queued' => 'orange',
                                            default => 'slate',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="cell-compact font-black text-slate-500 dark:text-slate-400">#{{ $order->id }}</td>
                                        <td>
                                            <a class="font-black text-blue-700 hover:text-blue-900 dark:text-blue-300" href="{{ route('orders.show', $order) }}">
                                                {{ $order->order_no }}
                                            </a>
                                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Gateway: {{ strtoupper($order->payment?->gateway ?? 'manual') }}</div>
                                        </td>
                                        <td>
                                            <div class="font-black text-slate-950 dark:text-white">{{ $order->user?->name ?? 'Unknown student' }}</div>
                                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $order->user?->email }}</div>
                                        </td>
                                        <td class="max-w-xs text-slate-600 dark:text-slate-300">
                                            {{ $order->items->pluck('course.title')->filter()->join(', ') ?: 'No course attached' }}
                                        </td>
                                        <td>
                                            <div class="space-y-2">
                                                <x-ui.badge :variant="$statusVariant">{{ $paymentStatus }}</x-ui.badge>
                                                @if($order->payment)
                                                    <x-ui.badge :variant="$order->payment->sourceVariant()">{{ $order->payment->sourceLabel() }}</x-ui.badge>
                                                    @if($order->payment->status === 'pending_verification')
                                                        <p class="max-w-xs text-xs font-semibold text-blue-600 dark:text-blue-300">
                                                            Receipt ID: {{ data_get($order->payment->gateway_payload, 'manual_proof.transaction_id', 'N/A') }}
                                                        </p>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="space-y-2">
                                                <x-ui.badge :variant="$fulfilmentVariant">{{ $fulfilmentText }}</x-ui.badge>
                                                @if($hasFailedFulfilment)
                                                    <p class="max-w-xs text-xs font-semibold text-red-600 dark:text-red-300">
                                                        {{ \Illuminate\Support\Str::limit($order->enrollments->firstWhere('status', 'failed')?->last_error, 80) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="font-black text-slate-950 dark:text-white">{{ $order->currency }} {{ number_format((float) $order->amount) }}</td>
                                        <td class="text-slate-500 dark:text-slate-400">{{ $order->created_at->format('d M Y') }}</td>
                                        <td>
                                            <div class="admin-actions">
                                                <x-ui.button href="{{ route('orders.show', $order) }}" variant="secondary" class="px-4 py-2">View</x-ui.button>
                                                @if(! $isPaid && ($canMarkPaidForTesting ?? false) && $order->payment?->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.orders.mark-paid-testing', $order) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="orange" class="px-4 py-2">Mark Paid Test</x-ui.button>
                                                    </form>
                                                @endif
                                                @if($order->payment?->status === 'pending_verification')
                                                    <form method="POST" action="{{ route('admin.orders.approve-manual-payment', $order) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" class="px-4 py-2">Approve</x-ui.button>
                                                    </form>
                                                @endif
                                                @if($isPaid)
                                                    <form method="POST" action="{{ route('admin.orders.retry-fulfillment', $order) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" :variant="$hasFailedFulfilment ? 'danger' : 'accent'" class="px-4 py-2">Retry Academy</x-ui.button>
                                                    </form>
                                                @endif
                                                @if($isPaid && $hasActiveFulfilment)
                                                    <form method="POST" action="{{ route('admin.orders.suspend-access', $order) }}" onsubmit="return confirm('Suspend academy access for this order?');">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="danger" class="px-4 py-2">Suspend</x-ui.button>
                                                    </form>
                                                @endif
                                                @if($isPaid && $hasSuspendedFulfilment)
                                                    <form method="POST" action="{{ route('admin.orders.reactivate-access', $order) }}" onsubmit="return confirm('Reactivate academy access for this order?');">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="orange" class="px-4 py-2">Reactivate</x-ui.button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <x-ui.empty-state title="No orders found" description="Try clearing filters or wait for the first Easypaisa checkout to complete." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">{{ $orders->links() }}</div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
