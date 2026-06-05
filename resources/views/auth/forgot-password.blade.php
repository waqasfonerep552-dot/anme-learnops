<x-guest-layout>
    <div>
        <p class="eyebrow">Account Recovery</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Reset your password</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Enter your email and we will send a secure password reset link.</p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="mt-2 block w-full rounded-2xl" type="email" name="email" :value="old('email')" placeholder="Enter your account email" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-ui.button type="submit" class="w-full py-4">Email Password Reset Link</x-ui.button>

        <p class="text-center text-sm font-semibold text-slate-600 dark:text-slate-300">
            Remembered your password?
            <a href="{{ route('login') }}" class="font-black text-blue-700 hover:text-blue-800">Back to login</a>
        </p>
    </form>
</x-guest-layout>
