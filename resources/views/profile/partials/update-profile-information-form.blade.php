<section>
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-950 dark:text-white">
                {{ __('Profile Information') }}
            </h2>
            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('Keep your business and learning account identity accurate for orders, receipts and ANME Academy access.') }}
            </p>
        </div>
        <x-ui.badge variant="blue">Identity</x-ui.badge>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Full Name (optional)')" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $user->name)" placeholder="Enter your full name" autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" placeholder="Enter your email address" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone Number')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $user->phone)" placeholder="0300 0000000" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4 text-sm text-orange-800 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-200">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="font-black underline decoration-2 underline-offset-4">
                        {{ __('Re-send verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-black text-green-700 dark:text-green-300">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>{{ __('Save Profile') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="rounded-full bg-green-50 px-4 py-2 text-sm font-black text-green-700 dark:bg-green-950 dark:text-green-200"
                >{{ __('Saved successfully.') }}</p>
            @endif
        </div>
    </form>
</section>
