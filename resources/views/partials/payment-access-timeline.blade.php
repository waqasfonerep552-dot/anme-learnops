@props([
    'payment' => null,
    'order' => null,
])

@php
    $paymentStatus = $payment?->status ?? $order?->payment?->status ?? $order?->status ?? 'pending';
    $orderStatus = $order?->status ?? $payment?->order?->status ?? $paymentStatus;
    $hasActiveAccess = $order?->enrollments?->contains('status', 'active') ?? false;
    $hasFailedAccess = $order?->enrollments?->contains('status', 'failed') ?? false;
    $hasSetupRequired = $order?->enrollments?->contains('status', 'setup_required') ?? false;
    $paymentSubmitted = in_array($paymentStatus, ['pending_verification', 'paid'], true) || $orderStatus === 'paid';
    $paymentApproved = $paymentStatus === 'paid' || $orderStatus === 'paid';

    $phases = [
        [
            'label' => 'Payment',
            'title' => $paymentSubmitted ? 'Receipt submitted' : 'Payment pending',
            'description' => $paymentSubmitted
                ? 'Receipt admin verification ke liye submit ho chuki hai.'
                : 'Exact amount Easypaisa/Raast se transfer karein.',
            'done' => $paymentSubmitted,
            'active' => ! $paymentSubmitted,
            'tone' => 'orange',
        ],
        [
            'label' => 'Verification',
            'title' => $paymentApproved ? 'Payment approved' : 'Admin review',
            'description' => $paymentApproved
                ? 'Payment paid mark ho chuki hai.'
                : 'Admin transaction ID aur receipt verify karega.',
            'done' => $paymentApproved,
            'active' => $paymentSubmitted && ! $paymentApproved,
            'tone' => 'blue',
        ],
        [
            'label' => 'Access',
            'title' => $hasActiveAccess ? 'Course access active' : ($hasFailedAccess ? 'Access needs retry' : ($hasSetupRequired ? 'Login setup required' : 'Access preparing')),
            'description' => $hasActiveAccess
                ? 'Student My Learning se course open kar sakta hai.'
                : ($hasFailedAccess
                    ? 'Admin retry/settings check required hai.'
                    : ($hasSetupRequired
                        ? 'Student ko Academy login setup complete karna hai.'
                        : 'Academy account aur enrolment queue mein hain.')),
            'done' => $hasActiveAccess,
            'active' => $paymentApproved && ! $hasActiveAccess,
            'tone' => $hasFailedAccess ? 'red' : 'green',
        ],
    ];

    $statusVariant = $hasActiveAccess
        ? 'green'
        : ($hasFailedAccess ? 'red' : ($hasSetupRequired ? 'blue' : ($paymentStatus === 'pending_verification' ? 'blue' : 'orange')));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-[2rem] border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/60']) }}>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">Access Timeline</p>
            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Payment to ANME Academy access</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Simple 3-stage flow: pay, verify, then activate course access.</p>
        </div>
        <x-ui.badge :variant="$statusVariant">
            {{ $hasActiveAccess ? 'Access active' : ($hasFailedAccess ? 'Needs retry' : ($hasSetupRequired ? 'Setup required' : str_replace('_', ' ', $paymentStatus))) }}
        </x-ui.badge>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach($phases as $index => $phase)
            @php
                $phaseVariant = $phase['done']
                    ? 'green'
                    : ($phase['active'] ? $phase['tone'] : 'slate');
            @endphp
            <div @class([
                'relative overflow-hidden rounded-3xl border p-5 transition',
                'border-green-100 bg-green-50 text-green-900 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-100' => $phaseVariant === 'green',
                'border-blue-200 bg-blue-50 text-blue-900 ring-2 ring-blue-100 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100 dark:ring-blue-950' => $phaseVariant === 'blue',
                'border-orange-200 bg-orange-50 text-orange-900 ring-2 ring-orange-100 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-100 dark:ring-orange-950' => $phaseVariant === 'orange',
                'border-red-100 bg-red-50 text-red-900 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100' => $phaseVariant === 'red',
                'border-slate-100 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400' => $phaseVariant === 'slate',
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span @class([
                            'grid h-10 w-10 place-items-center rounded-2xl text-sm font-black shadow-sm',
                            'bg-green-600 text-white' => $phase['done'],
                            'bg-blue-700 text-white' => $phaseVariant === 'blue',
                            'bg-orange-500 text-white' => $phaseVariant === 'orange',
                            'bg-red-600 text-white' => $phaseVariant === 'red',
                            'bg-white text-slate-500 dark:bg-slate-950 dark:text-slate-300' => $phaseVariant === 'slate',
                        ])>
                            {{ $phase['done'] ? '✓' : $index + 1 }}
                        </span>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] opacity-70">{{ $phase['label'] }}</p>
                            <h3 class="mt-1 text-base font-black">{{ $phase['title'] }}</h3>
                        </div>
                    </div>
                    @if($phase['active'])
                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-blue-700 shadow-sm dark:bg-slate-950 dark:text-blue-200">Now</span>
                    @endif
                </div>
                <p class="mt-4 text-sm font-semibold leading-6 opacity-80">{{ $phase['description'] }}</p>
            </div>
        @endforeach
    </div>
</div>
