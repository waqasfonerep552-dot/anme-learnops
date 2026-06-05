<x-guest-layout>
    <div>
        <p class="eyebrow">Verify Email</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white">Check your inbox</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Before getting started, verify your email address using the link we sent you.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 rounded-2xl bg-green-50 p-4 text-sm font-bold text-green-700 dark:bg-green-950/40 dark:text-green-300">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit">Resend Verification Email</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary">Log Out</x-ui.button>
        </form>
    </div>
</x-guest-layout>
