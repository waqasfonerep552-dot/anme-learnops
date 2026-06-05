<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Academy Access</p>
                <h1 class="mt-2 text-4xl font-black tracking-[-0.04em] text-slate-950 dark:text-white">Set your ANME Academy login</h1>
                <p class="mt-2 max-w-2xl text-slate-600 dark:text-slate-300">
                    Payment approve hone ke baad yahan username/password set karein. Ye credentials ANME Academy login ke liye use honge.
                </p>
            </div>
            <x-ui.button :href="route('dashboard')" variant="secondary">Back to My Learning</x-ui.button>
        </div>
    </x-slot>

    <div class="app-container py-10">
        @if(session('error'))
            <div class="mb-6 rounded-3xl border border-red-100 bg-red-50 p-5 font-bold text-red-700 shadow-sm dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <x-ui.card>
                <div class="flex items-start gap-4">
                    <x-ui.icon-tile name="shield" tone="indigo" />
                    <div>
                        <p class="eyebrow">Secure Setup</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Choose your Academy credentials</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Password plain text mein save nahi hoga. System sirf ANME Academy ko secure API ke through password set/update karega.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('academy-access.update') }}" class="mt-8 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="academy_username" class="text-sm font-black text-slate-800 dark:text-slate-100">Academy username</label>
                        <input
                            id="academy_username"
                            name="academy_username"
                            value="{{ $defaultUsername }}"
                            required
                            autocomplete="username"
                            class="admin-input mt-2 block w-full"
                            placeholder="e.g. awais6012"
                        >
                        @error('academy_username')
                            <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Lowercase letters, numbers, dot, dash aur underscore allowed hain.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="academy_password" class="text-sm font-black text-slate-800 dark:text-slate-100">Academy password</label>
                            <input
                                id="academy_password"
                                name="academy_password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="admin-input mt-2 block w-full"
                                placeholder="AcademyPass123!"
                            >
                            @error('academy_password')
                                <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="academy_password_confirmation" class="text-sm font-black text-slate-800 dark:text-slate-100">Confirm password</label>
                            <input
                                id="academy_password_confirmation"
                                name="academy_password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="admin-input mt-2 block w-full"
                                placeholder="Repeat password"
                            >
                        </div>
                    </div>

                    <p class="-mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                        Password mein uppercase, lowercase, number aur special character zaroor rakhein. Example:
                        <span class="font-black">AcademyPass123!</span>
                    </p>

                    <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 text-sm font-semibold leading-6 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
                        Is button ke baad system ANME Academy account banayega ya existing account password update karega, phir paid courses access activate karega.
                    </div>

                    <x-ui.button type="submit" class="w-full">
                        Activate Academy Access
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </x-ui.button>
                </form>
            </x-ui.card>

            <aside class="space-y-6">
                <x-ui.card dark>
                    <p class="text-xs font-black uppercase tracking-[0.3em] text-blue-200">Paid Courses</p>
                    <h2 class="mt-2 text-2xl font-black text-white">Access queue</h2>
                    <div class="mt-5 space-y-3">
                        @forelse($paidOrders as $order)
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-black text-white">{{ $order->order_no }}</span>
                                    <x-ui.badge variant="green">paid</x-ui.badge>
                                </div>
                                <div class="mt-3 space-y-2">
                                    @foreach($order->items as $item)
                                        <div class="rounded-xl bg-slate-950/30 p-3 text-sm font-bold text-blue-50">
                                            {{ $item->course?->title ?? 'Course' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-white/10 bg-white/10 p-4 text-sm font-bold text-blue-50">
                                Abhi koi approved paid order nahi mila. Payment approve hone ke baad setup unlock hoga.
                            </p>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <p class="eyebrow">Login Info</p>
                    <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            Academy username dashboard par show hoga.
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            Password aap khud set karte hain; hum plain password save nahi karte.
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                            Course active hone ke baad “Continue to ANME Academy” button se access milega.
                        </div>
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </div>
</x-app-layout>
