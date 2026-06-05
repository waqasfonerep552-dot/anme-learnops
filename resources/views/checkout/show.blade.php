<x-app-layout>
    {{-- Checkout: account + order create hota hai, academy access paid confirmation ke baad activate hota hai. --}}
    @php
        $phoneRequired = filter_var($platformSettings['student_phone_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
    @endphp
    <x-slot name="header">
        <div>
            <p class="eyebrow">Checkout</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Student Info / {{ $platformSettings['payment_gateway_label'] ?? 'Payment' }} / ANME Academy Access</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-300">Create your student account first. After payment verification, academy account access and enrolment run through secure queues.</p>
        </div>
    </x-slot>

    <div class="app-container py-10">
        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <x-ui.card padding="p-8">
                <form method="POST" action="{{ route('checkout.store', $course) }}">
                    @csrf
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Student details</h2>
                    <div class="mt-6 grid gap-5">
                        <div>
                            <x-input-label for="name" value="Full name (optional)" />
                            <x-text-input id="name" name="name" class="mt-2 block w-full" value="{{ old('name', auth()->user()->name ?? '') }}" placeholder="Enter student full name" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email address" />
                            <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="Enter student email address" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <x-input-label for="phone" value="Phone / {{ $platformSettings['payment_gateway_label'] ?? 'Payment' }} number" />
                                @if($phoneRequired)
                                    <x-ui.badge variant="orange">Required</x-ui.badge>
                                @else
                                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500">Optional</span>
                                @endif
                            </div>
                            <x-text-input id="phone" name="phone" class="mt-2 block w-full" value="{{ old('phone', auth()->user()->phone ?? '') }}" placeholder="03XX XXXXXXX" @required($phoneRequired) />
                            <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                @if($phoneRequired)
                                    Admin ne checkout phone required kiya hai, taake payment verification aur support easy rahe.
                                @else
                                    Phone optional hai; add karne se payment support aur admin verification easy hoti hai.
                                @endif
                            </p>
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password" value="{{ auth()->check() ? 'Account password (optional)' : 'Account password' }}" />
                            <x-text-input id="password" name="password" type="password" class="mt-2 block w-full" placeholder="{{ auth()->check() ? 'Leave blank if you are already logged in' : 'Enter account password' }}" @guest required @endguest />
                            @auth
                                <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Aap logged in hain, isliye password dobara dena zaroori nahi.</p>
                            @endauth
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                    </div>
                    <x-ui.button type="submit" class="mt-8 w-full py-4">{{ $platformSettings['checkout_button_label'] ?? 'Continue to payment' }}</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card class="h-fit">
                <x-ui.badge variant="orange">Order Summary</x-ui.badge>
                <h2 class="mt-4 text-2xl font-black text-slate-950 dark:text-white">{{ $course->title }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $course->short_description }}</p>
                <div class="mt-6 rounded-2xl bg-slate-950 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <span class="text-blue-100">Course price</span>
                        <strong class="text-2xl">{{ $course->currency }} {{ number_format((float) $course->price) }}</strong>
                    </div>
                </div>
                <div class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <p class="flex items-center gap-2"><x-ui.icon name="check" class="h-4 w-4 text-green-600 dark:text-green-300" /> Order and payment records created</p>
                    <p class="flex items-center gap-2"><x-ui.icon name="check" class="h-4 w-4 text-green-600 dark:text-green-300" /> Student role assigned through Spatie</p>
                    <p class="flex items-center gap-2"><x-ui.icon name="check" class="h-4 w-4 text-green-600 dark:text-green-300" /> Academy access activates only after paid status</p>
                </div>
                <div class="mt-5 rounded-2xl border border-orange-100 bg-orange-50 p-4 text-sm font-semibold leading-6 text-orange-800 dark:border-orange-900 dark:bg-orange-950/30 dark:text-orange-200">
                    {{ $platformSettings['payment_instructions'] ?? 'After payment verification, ANME Academy access is activated automatically.' }}
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
