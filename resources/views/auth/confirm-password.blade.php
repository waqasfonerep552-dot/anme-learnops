<x-guest-layout>
    <div>
        <p class="eyebrow">Secure Area</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Confirm your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Password" />
            <x-password-input id="password" class="mt-2 block w-full" name="password" placeholder="Enter your current password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-ui.button type="submit" class="w-full py-4">Confirm</x-ui.button>
    </form>
</x-guest-layout>
