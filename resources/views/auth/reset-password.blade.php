<x-guest-layout>
    <div>
        <p class="eyebrow">New Password</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Choose a secure password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Use a strong password to protect your learning and payment history.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="mt-2 block w-full rounded-2xl" type="email" name="email" :value="old('email', $request->email)" placeholder="Enter your account email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Password" />
                <x-password-input id="password" class="mt-2 block w-full" name="password" placeholder="Create a new password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirm password" />
                <x-password-input id="password_confirmation" class="mt-2 block w-full" name="password_confirmation" placeholder="Confirm new password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-ui.button type="submit" class="w-full py-4">Reset Password</x-ui.button>
    </form>
</x-guest-layout>
