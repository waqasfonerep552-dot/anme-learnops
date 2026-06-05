@php
    $manualEnabled = filter_var($platformSettings['easypaisa_manual_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $manualProof = data_get($payment->gateway_payload, 'manual_proof', []);
    $manualReview = data_get($payment->gateway_payload, 'manual_review', []);
    $qrPath = trim((string) ($platformSettings['easypaisa_qr_image_path'] ?? ''));
    $qrUrl = trim((string) ($platformSettings['easypaisa_qr_image_url'] ?? ''));
    $qrSrc = $qrPath !== ''
        ? \Illuminate\Support\Facades\Storage::url($qrPath)
        : ($qrUrl !== '' ? (\Illuminate\Support\Str::startsWith($qrUrl, ['http://', 'https://', '/']) ? $qrUrl : asset($qrUrl)) : '');
    $accountTitle = trim((string) ($platformSettings['easypaisa_account_title'] ?? ''));
    $accountNumber = trim((string) ($platformSettings['easypaisa_account_number'] ?? ''));
    $tillId = trim((string) ($platformSettings['easypaisa_till_id'] ?? ''));
    $payeeLabel = $accountTitle !== '' ? $accountTitle : 'Support will confirm';
    $paymentNumberLabel = $tillId !== '' ? $tillId : ($accountNumber !== '' ? $accountNumber : 'Contact support');
    $manualPaymentReady = $manualEnabled
        && filled($accountTitle)
        && (filled($tillId) || filled($accountNumber))
        && filled($qrSrc);
    $canSubmitProof = in_array($payment->status, ['pending', 'rejected'], true) && $manualPaymentReady;
    $payer = $payment->order?->user;
    $amountLabel = $payment->currency.' '.number_format((float) $payment->amount);
    $courseTitles = $payment->order?->items?->pluck('course.title')->filter()->join(', ') ?: 'Selected course';
@endphp

<x-app-layout>
    {{-- Pending payment: Easypaisa QR/account transfer aur manual admin verification ke liye. --}}
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow text-orange-600">Payment Pending</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Complete Easypaisa payment</h1>
                <p class="mt-2 max-w-3xl text-slate-600 dark:text-slate-300">
                    {{ $platformSettings['payment_pending_message'] ?? 'Pay the exact amount, upload receipt, then admin verification will activate your ANME Academy access.' }}
                </p>
            </div>
            <x-ui.badge variant="orange">Awaiting payment proof</x-ui.badge>
        </div>
    </x-slot>

    <div class="app-container py-8 sm:py-10">
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
                @include('partials.payment-access-timeline', ['payment' => $payment, 'order' => $payment->order])

                <x-ui.card padding="p-5 sm:p-7">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <x-ui.icon-tile name="user" tone="blue" />
                            <div>
                                <p class="eyebrow text-blue-600">Step 1</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Confirm order details</h2>
                                <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                    Receipt isi student aur order ke against verify hogi. Agar detail wrong ho, payment se pehle support ko contact karein.
                                </p>
                            </div>
                        </div>
                        <x-ui.badge :variant="$payment->sourceVariant()">{{ $payment->sourceLabel() }}</x-ui.badge>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                        <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Student</p>
                            <p class="mt-2 break-words text-lg font-black text-slate-950 dark:text-white">{{ $payer?->name ?? 'Student' }}</p>
                        </div>
                        <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Email</p>
                            <p class="mt-2 break-words text-lg font-black text-slate-950 dark:text-white">{{ $payer?->email ?? 'N/A' }}</p>
                        </div>
                        <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Phone</p>
                            @if($payer?->phone)
                                <p class="mt-2 break-words text-lg font-black text-slate-950 dark:text-white">{{ $payer->phone }}</p>
                            @else
                                <x-ui.badge variant="slate" class="mt-2">Optional / not added</x-ui.badge>
                            @endif
                        </div>
                        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
                            <p class="text-xs font-black uppercase tracking-widest text-blue-500 dark:text-blue-200">Exact amount</p>
                            <p class="mt-2 text-2xl font-black text-blue-800 dark:text-blue-100">{{ $amountLabel }}</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-5 sm:p-7">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <x-ui.icon-tile name="payment" tone="orange" />
                            <div>
                                <p class="eyebrow text-orange-600">Step 2</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Pay exact amount by Easypaisa/Raast</h2>
                                <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                    QR scan karein ya Till ID use karein. Amount exact hona chahiye warna admin approval delay ho sakta hai.
                                </p>
                            </div>
                        </div>
                        @if($qrSrc)
                            <x-ui.button href="{{ $qrSrc }}" target="_blank" rel="noopener" variant="secondary">
                                View full QR
                            </x-ui.button>
                        @endif
                    </div>

                    <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem] 2xl:grid-cols-[minmax(0,1fr)_21rem]">
                        <div class="rounded-[2rem] border border-orange-100 bg-gradient-to-br from-orange-50 to-white p-5 dark:border-orange-900/50 dark:from-orange-950/30 dark:to-slate-950">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-950/60">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Pay to</p>
                                    <p class="mt-2 break-words text-xl font-black text-slate-950 dark:text-white">{{ $payeeLabel }}</p>
                                </div>
                                <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-950/60">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Till ID / number</p>
                                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-mono text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-lg 2xl:text-xl">{{ $paymentNumberLabel }}</p>
                                        @if($paymentNumberLabel !== 'Contact support')
                                            <button
                                                type="button"
                                                x-data
                                                x-on:click="navigator.clipboard?.writeText(@js($paymentNumberLabel))"
                                                class="shrink-0 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-blue-50 hover:text-blue-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                                            >
                                                Copy
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-950/60 sm:col-span-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Order reference</p>
                                    <p class="mt-2 break-all font-mono text-sm font-black leading-6 text-slate-950 dark:text-white sm:text-base">{{ $payment->reference_no }}</p>
                                </div>
                                <div class="rounded-2xl bg-blue-700 p-4 text-white shadow-lg shadow-blue-900/20 sm:col-span-2">
                                    <p class="text-xs font-black uppercase tracking-widest text-blue-100">Pay exactly</p>
                                    <p class="mt-2 text-3xl font-black">{{ $amountLabel }}</p>
                                </div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-orange-200 bg-white p-4 text-sm font-bold leading-6 text-orange-800 dark:border-orange-900/50 dark:bg-slate-950/60 dark:text-orange-200">
                                {{ $platformSettings['easypaisa_payment_note'] ?? 'Send payment through Easypaisa/Raast, then upload your receipt for admin verification.' }}
                            </div>
                        </div>

                        <div class="rounded-[2rem] border border-slate-100 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-5">
                            <h3 class="text-center text-sm font-black uppercase tracking-widest text-slate-400">Scan to pay</h3>
                            @if($qrSrc)
                                <img src="{{ $qrSrc }}" alt="Easypaisa/Raast QR for {{ $payeeLabel }}" class="mt-4 aspect-square w-full rounded-3xl border border-blue-100 bg-white object-contain p-4 shadow-sm dark:border-slate-800">
                                <p class="mt-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400">QR scan karne ke baad exact amount confirm karein.</p>
                            @else
                                <div class="mt-4 grid aspect-square w-full place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 text-center dark:border-slate-800 dark:bg-slate-900">
                                    <div>
                                        <x-ui.icon-tile name="payment" tone="orange" class="mx-auto" />
                                        <p class="mt-3 text-sm font-bold text-slate-500 dark:text-slate-400">Payment QR unavailable</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-ui.card>

                @if($payment->status === 'pending_verification')
                    <x-ui.card padding="p-5 sm:p-7">
                        <div class="flex items-start gap-4">
                            <x-ui.icon-tile name="check" tone="blue" />
                            <div>
                                <p class="eyebrow text-blue-600">Receipt Submitted</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Admin verification pending</h2>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                    Receipt submit ho chuki hai. Admin statement/app se transaction match karega; approval ke baad course access process hoga.
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Transaction</p>
                                <p class="mt-2 break-all font-black text-slate-950 dark:text-white">{{ data_get($manualProof, 'transaction_id') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Paid amount</p>
                                <p class="mt-2 font-black text-slate-950 dark:text-white">PKR {{ number_format((float) data_get($manualProof, 'paid_amount')) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Submitted</p>
                                <p class="mt-2 font-black text-slate-950 dark:text-white">{{ filled(data_get($manualProof, 'submitted_at')) ? \Illuminate\Support\Carbon::parse(data_get($manualProof, 'submitted_at'))->diffForHumans() : 'Pending' }}</p>
                            </div>
                        </div>
                    </x-ui.card>
                @elseif($manualEnabled && $manualPaymentReady)
                    <x-ui.card padding="p-5 sm:p-7">
                        <div class="flex items-start gap-4">
                            <x-ui.icon-tile name="payment" tone="orange" />
                            <div>
                                <p class="eyebrow text-orange-600">Step 3</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Upload payment receipt</h2>
                                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                    Proof upload se order paid nahi hota. Admin transaction verify karega, phir access activate hoga.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 rounded-3xl border border-blue-100 bg-blue-50 p-5 text-sm font-bold text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-100 sm:grid-cols-3">
                            <div>
                                <p class="text-xs uppercase tracking-widest opacity-70">Pay to</p>
                                <p class="mt-1">{{ $payeeLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest opacity-70">Till ID / Number</p>
                                <p class="mt-1">{{ $paymentNumberLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-widest opacity-70">Amount</p>
                                <p class="mt-1">{{ $amountLabel }}</p>
                            </div>
                        </div>

                        @if($payment->status === 'rejected')
                            <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm font-bold text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                                Previous proof rejected. {{ data_get($manualReview, 'admin_note') ?: 'Please check transaction ID/receipt and submit again.' }}
                            </div>
                        @endif

                        @if($canSubmitProof)
                            <form method="POST" action="{{ route('payments.manual-proof.store', $payment) }}" enctype="multipart/form-data" class="mt-6 grid gap-5 md:grid-cols-2">
                                @csrf
                                <div>
                                    <x-input-label for="transaction_id" value="Easypaisa transaction ID" />
                                    <x-text-input id="transaction_id" name="transaction_id" class="mt-2 block w-full" value="{{ old('transaction_id') }}" placeholder="Example: 1234567890 / reference ID" required />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Receipt/app screen par jo transaction/reference ID hai woh add karein.</p>
                                    <x-input-error :messages="$errors->get('transaction_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="sender_phone" value="Sender phone number" />
                                    <x-text-input id="sender_phone" name="sender_phone" class="mt-2 block w-full" value="{{ old('sender_phone') }}" placeholder="03XX XXXXXXX" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Jis number/account se payment bheji hai.</p>
                                    <x-input-error :messages="$errors->get('sender_phone')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="paid_amount" value="Paid amount" />
                                    <x-text-input id="paid_amount" name="paid_amount" type="number" step="0.01" min="0" class="mt-2 block w-full" value="{{ old('paid_amount', (float) $payment->amount) }}" placeholder="Amount paid" required />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Order amount ke barabar hona chahiye: {{ $amountLabel }}.</p>
                                    <x-input-error :messages="$errors->get('paid_amount')" class="mt-2" />
                                </div>
                                <div x-data="{ fileName: '' }">
                                    <x-input-label for="receipt" value="Receipt screenshot / PDF" />
                                    <label for="receipt" class="mt-2 flex min-h-32 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 p-5 text-center transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-blue-900 dark:hover:bg-blue-950/30">
                                        <x-ui.icon-tile name="payment" tone="blue" size="sm" />
                                        <span class="mt-3 text-sm font-black text-slate-800 dark:text-slate-100" x-text="fileName || 'Upload screenshot or PDF'">Upload screenshot or PDF</span>
                                        <span class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">JPG, PNG or PDF accepted</span>
                                    </label>
                                    <input
                                        id="receipt"
                                        name="receipt"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="sr-only"
                                        required
                                        x-on:change="fileName = $event.target.files?.[0]?.name || ''"
                                    >
                                    <x-input-error :messages="$errors->get('receipt')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-ui.button type="submit" class="w-full py-4">
                                        Submit receipt for verification
                                        <x-ui.icon name="arrow" class="h-4 w-4" />
                                    </x-ui.button>
                                </div>
                            </form>
                        @endif
                    </x-ui.card>
                @else
                    <x-ui.card padding="p-8">
                        <x-ui.empty-state title="Payment details need setup" description="Please contact support before sending payment. Course access will activate only after verified payment confirmation." />
                    </x-ui.card>
                @endif
            </div>

            <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
                <x-ui.card padding="p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <x-ui.icon-tile name="payment" tone="orange" size="sm" />
                        <div>
                            <p class="eyebrow text-orange-600">Payment Summary</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Pay {{ $amountLabel }}</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">To {{ $payeeLabel }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Course</p>
                            <p class="mt-2 font-black text-slate-950 dark:text-white">{{ $courseTitles }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Till ID / Number</p>
                            <p class="mt-2 break-all font-black text-slate-950 dark:text-white">{{ $paymentNumberLabel }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-950 p-4 text-white dark:bg-white dark:text-slate-950">
                            <p class="text-xs font-black uppercase tracking-widest opacity-60">Order reference</p>
                            <p class="mt-2 break-all font-black">{{ $payment->reference_no }}</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-orange-100 bg-orange-50 p-4 text-sm font-bold leading-6 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/40 dark:text-orange-200">
                        Fake screenshots reject ho sakte hain. Admin actual transaction verify karega; approval ke baad access milega.
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-5 sm:p-6">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">Checklist</h2>
                    <ol class="mt-5 space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-blue-700 text-xs text-white">1</span> Student aur order amount confirm karein.</li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-blue-700 text-xs text-white">2</span> QR/Till ID se exact amount pay karein.</li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-blue-700 text-xs text-white">3</span> Transaction ID aur receipt upload karein.</li>
                        <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-xl bg-green-600 text-xs text-white">4</span> Admin approval ke baad course access active hoga.</li>
                    </ol>

                    <x-ui.button :href="route('orders.show', $payment->order)" variant="secondary" class="mt-5 w-full">
                        View Order
                    </x-ui.button>
                </x-ui.card>
            </aside>
        </div>
    </div>
</x-app-layout>
