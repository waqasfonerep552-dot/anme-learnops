<x-guest-layout>
    <div>
        <p class="eyebrow">Welcome Back</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Log in</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Access your courses, orders and learning dashboard.</p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

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

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email" class="mt-2 block w-full rounded-xl" type="email" name="email" :value="old('email')" placeholder="you@example.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Password" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-bold text-blue-700 hover:text-blue-800" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <x-password-input id="password" class="mt-2 block w-full" name="password" placeholder="Your password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-500" name="remember">
            Remember me on this device
        </label>

        <x-ui.button type="submit" class="w-full py-3.5">Log in</x-ui.button>

        <p class="text-center text-sm font-semibold text-slate-600 dark:text-slate-300">
            New to ANME LearnOps?
            <a href="{{ route('register') }}" class="font-black text-blue-700 hover:text-blue-800">Create an account</a>
        </p>
    </form>
</x-guest-layout>
