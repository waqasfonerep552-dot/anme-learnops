<x-app-layout>
    {{-- Order detail: student/admin ko purchase, payment aur course items ka full record milta hai. --}}
    @php
        $manualProof = data_get($order->payment?->gateway_payload, 'manual_proof', []);
        $manualReview = data_get($order->payment?->gateway_payload, 'manual_review', []);
        $receiptPath = data_get($manualProof, 'receipt_path');
        $receiptUrl = $manualReviewChecks['receipt_url'] ?? (filled($receiptPath) ? \Illuminate\Support\Facades\Storage::url($receiptPath) : null);
        $orderStatusVariant = match ($order->payment?->status ?? $order->status) {
            'paid' => 'green',
            'pending_verification' => 'blue',
            'rejected', 'failed', 'cancelled' => 'red',
            default => 'orange',
        };
        $hasActiveFulfilment = $order->enrollments->contains('status', 'active');
        $hasSuspendedFulfilment = $order->enrollments->contains('status', 'suspended');
        $showManualReview = auth()->user()?->isAdmin() && filled($manualProof);
        $showManualReviewActions = $showManualReview && $order->payment?->status === 'pending_verification';
        $reviewChecklist = [
            ['label' => 'Transaction ID present', 'ok' => (bool) data_get($manualReviewChecks, 'checks.transaction_present'), 'hint' => data_get($manualReviewChecks, 'transaction_id') ?: 'Missing'],
            ['label' => 'Amount matches order', 'ok' => (bool) data_get($manualReviewChecks, 'checks.amount_matches'), 'hint' => ($order->payment?->currency ?? $order->currency).' '.number_format((float) data_get($manualReviewChecks, 'submitted_amount', 0)).' / '.($order->payment?->currency ?? $order->currency).' '.number_format((float) data_get($manualReviewChecks, 'expected_amount', $order->amount))],
            ['label' => 'Transaction ID is unique', 'ok' => (bool) data_get($manualReviewChecks, 'checks.unique_reference'), 'hint' => 'No duplicate paid/review record'],
            ['label' => 'Receipt file attached', 'ok' => (bool) data_get($manualReviewChecks, 'checks.receipt_attached'), 'hint' => $receiptPath ?: 'Missing'],
        ];
        $academyBaseUrl = rtrim((string) config('moodle.base_url'), '/');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="eyebrow">Order</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $order->order_no }}</h1>
                <p class="mt-2 text-slate-600 dark:text-slate-300">Payment and academy access status for this purchase.</p>
            </div>
            <x-ui.badge :variant="$orderStatusVariant">{{ $order->payment?->status ?? $order->status }}</x-ui.badge>
        </div>
    </x-slot>

    <div class="app-container py-10">
        @if(session('status'))
            <div class="mb-6 rounded-3xl border border-green-100 bg-green-50 p-5 font-bold text-green-700 shadow-sm dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 rounded-3xl border border-red-100 bg-red-50 p-5 font-bold text-red-700 shadow-sm dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        @include('partials.payment-access-timeline', ['payment' => $order->payment, 'order' => $order])

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <x-ui.card>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-ui.icon-tile name="courses" tone="blue" size="sm" />
                            <h2 class="text-xl font-black text-slate-950 dark:text-white">Courses</h2>
                        </div>
                        <x-ui.badge variant="blue">{{ $order->items->count() }} item(s)</x-ui.badge>
                    </div>
                    <div class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($order->items as $item)
                            <div class="flex items-center justify-between gap-4 py-4">
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">{{ $item->course->title }}</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Course code: AC-{{ $item->course->moodle_course_id }}</p>
                                </div>
                                <strong class="text-slate-950 dark:text-white">{{ $item->currency }} {{ number_format((float) $item->price) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                @if($showManualReview)
                    <x-ui.card id="receipt-review" padding="p-7">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-3">
                                <x-ui.icon-tile name="payment" tone="orange" size="sm" />
                                <div>
                                    <p class="eyebrow text-orange-600">Admin Review</p>
                                    <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Receipt verification workspace</h2>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                        Screenshot/reference ko verify karein. Approve sirf tab karein jab checklist green ho.
                                    </p>
                                </div>
                            </div>
                            <x-ui.badge :variant="$order->payment?->sourceVariant()">{{ $order->payment?->sourceLabel() }}</x-ui.badge>
                        </div>

                        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                            <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="font-black text-slate-950 dark:text-white">Uploaded receipt</h3>
                                    @if($receiptUrl)
                                        <x-ui.button href="{{ $receiptUrl }}" target="_blank" rel="noopener" variant="secondary" class="min-h-10 px-4 py-2">Open File</x-ui.button>
                                    @endif
                                </div>

                                @if($receiptUrl && data_get($manualReviewChecks, 'receipt_is_image'))
                                    <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="mt-4 block overflow-hidden rounded-3xl border border-white bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                        <img src="{{ $receiptUrl }}" alt="Uploaded Easypaisa receipt for {{ $order->order_no }}" class="max-h-[520px] w-full object-contain">
                                    </a>
                                @elseif($receiptUrl)
                                    <div class="mt-4 rounded-3xl border border-dashed border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900">
                                        <x-ui.icon-tile name="orders" tone="blue" class="mx-auto" />
                                        <h4 class="mt-4 font-black text-slate-950 dark:text-white">Receipt file attached</h4>
                                        <p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">PDF/file preview browser mein open karein.</p>
                                    </div>
                                @else
                                    <x-ui.empty-state title="No receipt file attached" description="Student needs to upload a screenshot or PDF before admin can approve." />
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                                    <h3 class="font-black text-slate-950 dark:text-white">Verification checklist</h3>
                                    <div class="mt-4 space-y-3">
                                        @foreach($reviewChecklist as $check)
                                            <div class="flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl {{ $check['ok'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-200' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200' }}">
                                                    <x-ui.icon :name="$check['ok'] ? 'check' : 'x'" class="h-4 w-4" />
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="font-black text-slate-950 dark:text-white">{{ $check['label'] }}</p>
                                                    <p class="mt-1 break-all text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $check['hint'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                @if($showManualReviewActions)
                                    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-900/50 dark:bg-blue-950/30">
                                        <h3 class="font-black text-slate-950 dark:text-white">Admin decision</h3>
                                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                            Approve se payment paid hogi aur academy fulfilment queue start hogi. Reject reason student ko notification mein milega.
                                        </p>

                                        <form method="POST" action="{{ route('admin.orders.approve-manual-payment', $order) }}" class="mt-4">
                                            @csrf
                                            <x-ui.button type="submit" class="w-full" :disabled="! data_get($manualReviewChecks, 'ready_to_approve')">
                                                <x-ui.icon name="check" class="h-4 w-4" />
                                                Approve Easypaisa Receipt
                                            </x-ui.button>
                                            @unless(data_get($manualReviewChecks, 'ready_to_approve'))
                                                <p class="mt-2 text-xs font-bold text-orange-700 dark:text-orange-200">Checklist clear nahi hai; approve disabled hai.</p>
                                            @endunless
                                        </form>

                                        <form method="POST" action="{{ route('admin.orders.reject-manual-payment', $order) }}" class="mt-4">
                                            @csrf
                                            <textarea name="admin_note" rows="3" class="admin-input block w-full" placeholder="Required reason: e.g. amount mismatch, wrong transaction ID, unclear screenshot" required minlength="5"></textarea>
                                            <x-ui.button type="submit" variant="danger" class="mt-3 w-full">
                                                Reject Receipt
                                            </x-ui.button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                <x-ui.card>
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-3">
                            <x-ui.icon-tile name="academy" tone="indigo" size="sm" />
                            <div>
                                <p class="eyebrow">Access Fulfilment</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">ANME Academy access</h2>
                                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                    Payment approve hone ke baad system account setup aur course enrolment ko track karta hai. Har course ka status neeche clear hai.
                                </p>
                            </div>
                        </div>
                        <x-ui.badge variant="indigo">{{ $order->enrollments->count() }} access record(s)</x-ui.badge>
                    </div>

                    <div class="mt-6 grid gap-4">
                        @forelse($order->enrollments as $enrollment)
                            @php
                                $course = $enrollment->course;
                                $variant = match ($enrollment->status) {
                                    'active' => 'green',
                                    'failed' => 'red',
                                    'setup_required' => 'blue',
                                    'suspended' => 'orange',
                                    'pending' => 'orange',
                                    default => 'slate',
                                };
                                $statusCopy = match ($enrollment->status) {
                                    'active' => 'Course access active hai. Student ab ANME Academy mein course open kar sakta hai.',
                                    'setup_required' => 'Payment paid hai, lekin student ko pehle Academy login setup complete karna hai.',
                                    'failed' => 'Fulfilment fail hui hai. Admin retry ya settings check kare.',
                                    'suspended' => 'Access suspended hai; student course open nahi kar sakta jab tak reactivate na ho.',
                                    'pending' => 'Access queue mein hai; fulfilment complete hone ka wait hai.',
                                    default => 'Access status update ka wait hai.',
                                };
                            @endphp
                            <div class="rounded-[1.75rem] border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex min-w-0 gap-4">
                                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white text-lg font-black text-blue-700 ring-1 ring-blue-100 dark:bg-slate-900 dark:text-blue-200 dark:ring-blue-900">
                                            {{ str($course?->title ?? 'Course')->substr(0, 1)->upper() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-ui.badge :variant="$variant">{{ str($enrollment->status)->replace('_', ' ') }}</x-ui.badge>
                                                @if($course?->moodle_course_id)
                                                    <span class="text-xs font-black uppercase tracking-widest text-slate-400">AC-{{ $course->moodle_course_id }}</span>
                                                @endif
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $course?->title ?? 'Course' }}</h3>
                                            <p class="mt-1 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">{{ $statusCopy }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                                                <span>Enrolled: {{ $enrollment->enrolled_at?->format('M d, Y') ?? 'Waiting' }}</span>
                                                <span>Starts: {{ $enrollment->access_starts_at?->format('M d, Y') ?? 'After activation' }}</span>
                                                <span>Ends: {{ $enrollment->access_ends_at?->format('M d, Y') ?? 'Lifetime' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-full lg:w-64">
                                        @if($enrollment->status === 'active' && $course?->moodle_course_id)
                                            <x-ui.button
                                                href="{{ $academyBaseUrl }}/course/view.php?id={{ $course->moodle_course_id }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="w-full"
                                            >
                                                Continue to ANME Academy
                                                <x-ui.icon name="arrow" class="h-4 w-4" />
                                            </x-ui.button>
                                        @elseif($enrollment->status === 'setup_required' && auth()->id() === $order->user_id)
                                            <x-ui.button href="{{ route('academy-access.edit') }}" variant="orange" class="w-full">
                                                Set Academy Login
                                                <x-ui.icon name="arrow" class="h-4 w-4" />
                                            </x-ui.button>
                                        @elseif($enrollment->status === 'suspended')
                                            <div class="rounded-2xl bg-orange-50 p-3 text-sm font-black text-orange-700 dark:bg-orange-950/40 dark:text-orange-200">
                                                Suspended by admin
                                            </div>
                                        @elseif($enrollment->status === 'failed')
                                            <div class="rounded-2xl bg-red-50 p-3 text-sm font-black text-red-700 dark:bg-red-950/40 dark:text-red-200">
                                                Admin retry needed
                                            </div>
                                        @else
                                            <div class="rounded-2xl bg-white p-3 text-sm font-black text-slate-600 ring-1 ring-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-800">
                                                Waiting for fulfilment
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($enrollment->last_error)
                                    <p class="mt-4 rounded-2xl bg-red-50 p-3 text-sm font-semibold leading-6 text-red-700 dark:bg-red-950/40 dark:text-red-200">{{ $enrollment->last_error }}</p>
                                @endif
                            </div>
                        @empty
                            @if(($order->status === 'paid' || $order->payment?->status === 'paid') && auth()->id() === $order->user_id && ! auth()->user()->hasAcademyPassword())
                                <x-ui.empty-state title="Set Academy login first" description="Payment approved hai. Academy username/password set karte hi course access activate hoga." action="Set Academy Login" :href="route('academy-access.edit')" />
                            @else
                                <x-ui.empty-state title="No academy access yet" description="Paid orders approval ke baad course access queue yahan show hogi." />
                            @endif
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card class="h-fit">
                <div class="flex items-center gap-3">
                    <x-ui.icon-tile name="payment" tone="orange" size="sm" />
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">Payment</h2>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Gateway</dt><dd class="font-bold uppercase text-slate-950 dark:text-white">{{ $order->payment?->gateway ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Source</dt>
                        <dd>
                            @if($order->payment)
                                <x-ui.badge :variant="$order->payment->sourceVariant()">{{ $order->payment->sourceLabel() }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="slate">No payment</x-ui.badge>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Reference</dt><dd class="font-bold text-slate-950 dark:text-white">{{ $order->payment?->reference_no ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Transaction</dt><dd class="font-bold text-slate-950 dark:text-white">{{ $order->payment?->transaction_id ?? 'Pending' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Total</dt><dd class="font-black text-slate-950 dark:text-white">{{ $order->currency }} {{ number_format((float) $order->amount) }}</dd></div>
                </dl>

                @if($manualProof)
                    <div class="mt-6 rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-black text-slate-950 dark:text-white">Manual Easypaisa proof</h3>
                            <x-ui.badge :variant="$order->payment?->status === 'pending_verification' ? 'blue' : ($order->payment?->status === 'rejected' ? 'red' : 'green')">
                                {{ $order->payment?->sourceLabel() }}
                            </x-ui.badge>
                        </div>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Submitted ID</dt><dd class="text-right font-bold text-slate-950 dark:text-white">{{ data_get($manualProof, 'transaction_id') ?: 'N/A' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Sender phone</dt><dd class="text-right font-bold text-slate-950 dark:text-white">{{ data_get($manualProof, 'sender_phone') ?: 'N/A' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Submitted amount</dt><dd class="text-right font-bold text-slate-950 dark:text-white">PKR {{ number_format((float) data_get($manualProof, 'paid_amount')) }}</dd></div>
                        </dl>
                        @if($receiptUrl)
                            <x-ui.button href="{{ $receiptUrl }}" target="_blank" rel="noopener" variant="secondary" class="mt-4 w-full">
                                View Receipt
                            </x-ui.button>
                        @endif
                        @if($order->payment?->status === 'rejected')
                            <p class="mt-4 rounded-2xl bg-red-50 p-3 text-xs font-bold leading-5 text-red-700 dark:bg-red-950/40 dark:text-red-200">
                                Rejected: {{ data_get($manualReview, 'admin_note') ?: 'Please submit corrected Easypaisa proof.' }}
                            </p>
                            @if(auth()->id() === $order->user_id)
                                <x-ui.button href="{{ route('payments.pending', $order->payment) }}" variant="orange" class="mt-4 w-full">
                                    Submit Corrected Proof
                                </x-ui.button>
                            @endif
                        @endif
                    </div>
                @endif

                @if($showManualReviewActions)
                    <x-ui.button href="#receipt-review" variant="orange" class="mt-6 w-full">
                        <x-ui.icon name="payment" class="h-4 w-4" />
                        Review Receipt
                    </x-ui.button>
                @endif

                @if(auth()->user()?->isAdmin() && ($canMarkPaidForTesting ?? false) && $order->status !== 'paid' && $order->payment?->status === 'pending')
                    <form method="POST" action="{{ route('admin.orders.mark-paid-testing', $order) }}" class="mt-6">
                        @csrf
                        <x-ui.button type="submit" variant="orange" class="w-full">
                            <x-ui.icon name="check" class="h-4 w-4" />
                            Mark Paid for Testing
                        </x-ui.button>
                    </form>
                    <p class="mt-3 text-xs font-semibold leading-5 text-orange-600 dark:text-orange-300">
                        Local testing only. Real student access should activate from verified Easypaisa webhook.
                    </p>
                @endif
                @if(auth()->user()?->isAdmin() && ($order->status === 'paid' || $order->payment?->status === 'paid'))
                    <form method="POST" action="{{ route('admin.orders.retry-fulfillment', $order) }}" class="mt-6">
                        @csrf
                        <x-ui.button type="submit" variant="accent" class="w-full">
                            <x-ui.icon name="settings" class="h-4 w-4" />
                            Retry Academy Access
                        </x-ui.button>
                    </form>
                    <p class="mt-3 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">
                        {{ $platformSettings['fulfilment_retry_instructions'] ?? 'Use retry if academy access did not activate after paid payment.' }}
                    </p>
                @endif
                @if(auth()->user()?->isAdmin() && ($order->status === 'paid' || $order->payment?->status === 'paid') && $hasActiveFulfilment)
                    <form method="POST" action="{{ route('admin.orders.suspend-access', $order) }}" class="mt-3" onsubmit="return confirm('Suspend academy access for this order?');">
                        @csrf
                        <x-ui.button type="submit" variant="danger" class="w-full">
                            <x-ui.icon name="shield" class="h-4 w-4" />
                            Suspend Academy Access
                        </x-ui.button>
                    </form>
                @endif
                @if(auth()->user()?->isAdmin() && ($order->status === 'paid' || $order->payment?->status === 'paid') && $hasSuspendedFulfilment)
                    <form method="POST" action="{{ route('admin.orders.reactivate-access', $order) }}" class="mt-3" onsubmit="return confirm('Reactivate academy access for this order?');">
                        @csrf
                        <x-ui.button type="submit" variant="orange" class="w-full">
                            <x-ui.icon name="check" class="h-4 w-4" />
                            Reactivate Academy Access
                        </x-ui.button>
                    </form>
                @endif
                <x-ui.button :href="route('dashboard')" class="mt-6 w-full">
                    Go to Dashboard
                    <x-ui.icon name="arrow" class="h-4 w-4" />
                </x-ui.button>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
