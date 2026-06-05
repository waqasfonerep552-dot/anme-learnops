<section>
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-950 dark:text-white">
                {{ __('Update Password') }}
            </h2>
            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('Use a strong password to protect orders, payments and ANME Academy learning access.') }}
            </p>
        </div>
        <x-ui.badge variant="indigo">Security</x-ui.badge>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-password-input id="update_password_current_password" name="current_password" class="mt-2 block w-full" placeholder="Enter current password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <x-password-input id="update_password_password" name="password" class="mt-2 block w-full" placeholder="Enter new password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-password-input id="update_password_password_confirmation" name="password_confirmation" class="mt-2 block w-full" placeholder="Confirm new password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>{{ __('Update Password') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="rounded-full bg-green-50 px-4 py-2 text-sm font-black text-green-700 dark:bg-green-950 dark:text-green-200"
                >{{ __('Password updated.') }}</p>
            @endif
        </div>
    </form>
</section>
