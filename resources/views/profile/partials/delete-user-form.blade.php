<section class="space-y-6">
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-950 dark:text-white">
                {{ __('Delete Account') }}
            </h2>
            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ __('This permanently removes your business website account. Keep order records before deleting anything important.') }}
            </p>
        </div>
        <x-ui.badge variant="red">Danger</x-ui.badge>
    </header>

    <div class="rounded-3xl border border-red-100 bg-red-50/70 p-5 dark:border-red-900 dark:bg-red-950/30">
        <p class="text-sm font-semibold leading-6 text-red-800 dark:text-red-200">
            {{ __('Deleting your account cannot be undone. Your ANME Academy learning access may need separate admin review depending on business policy.') }}
        </p>

        <x-danger-button
            class="mt-5"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete Account') }}</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-2xl font-black text-slate-950">
                {{ __('Are you sure?') }}
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-600">
                {{ __('Enter your password to permanently delete your account. This action cannot be reversed.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="{{ __('Enter your password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
