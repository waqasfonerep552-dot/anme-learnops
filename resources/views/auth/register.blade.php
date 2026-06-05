<x-guest-layout>
    <div>
        <p class="eyebrow">Create Account</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Create account</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Register once and start your ANME Academy learning journey.</p>
    </div>

    <div class="mt-6">
        @if (config('services.google.client_id') && config('services.google.client_secret'))
            <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-black text-slate-800 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 dark:border-white/10 dark:bg-slate-950 dark:text-white dark:hover:bg-slate-800">
                <x-google-icon />
                Continue with Google
            </a>
            <div class="my-5 flex items-center gap-3">
                <span class="h-px flex-1 bg-slate-200 dark:bg-white/10"></span>
                <span class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">or</span>
                <span class="h-px flex-1 bg-slate-200 dark:bg-white/10"></span>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Full name (optional)" />
            <x-text-input id="name" class="mt-2 block w-full rounded-xl" type="text" name="name" :value="old('name')" placeholder="Your name" autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="mt-2 block w-full rounded-xl" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Password" />
                <x-password-input id="password" class="mt-2 block w-full" name="password" placeholder="Create password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirm password" />
                <x-password-input id="password_confirmation" class="mt-2 block w-full" name="password_confirmation" placeholder="Confirm password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-ui.button type="submit" class="w-full py-3.5">Create Account</x-ui.button>

        <p class="text-center text-sm font-semibold text-slate-600 dark:text-slate-300">
            Already registered?
            <a href="{{ route('login') }}" class="font-black text-blue-700 hover:text-blue-800">Log in</a>
        </p>
    </form>
</x-guest-layout>
