@php
    // Layout settings DB se cached aati hain; admin panel se business copy update hoti hai.
    $platformSettings = \App\Models\PlatformSetting::publicValues([
        'business_name' => config('app.name', 'ANME LearnOps'),
        'business_tagline' => 'Business + ANME Academy',
        'academy_name' => 'ANME Academy',
        'academy_url' => '',
        'academy_access_label' => 'Open ANME Academy',
        'support_email' => 'support@example.com',
        'support_phone' => '03000000000',
        'support_whatsapp' => '03000000000',
        'default_currency' => 'PKR',
        'payment_gateway_label' => 'Easypaisa',
        'checkout_button_label' => 'Continue to payment',
        'easypaisa_manual_enabled' => '1',
        'easypaisa_account_title' => '',
        'easypaisa_account_number' => '',
        'easypaisa_till_id' => '',
        'easypaisa_qr_image_url' => '',
        'easypaisa_qr_image_path' => '',
        'easypaisa_payment_note' => 'Scan the QR code or transfer to the Easypaisa account, then upload your receipt for admin verification.',
        'payment_instructions' => 'After payment verification, ANME Academy access is activated automatically.',
        'payment_pending_message' => 'Your payment is pending. ANME Academy access will be processed after confirmation.',
        'payment_success_message' => 'Payment confirmed. ANME Academy access is being prepared in the background.',
        'fulfilment_retry_instructions' => 'If academy access fails after paid order, admin can retry fulfilment.',
        'footer_note' => 'Production-ready foundation for training businesses.',
        'footer_theme' => 'royal',
        'social_facebook_url' => '',
        'social_instagram_url' => '',
        'social_youtube_url' => '',
        'social_linkedin_url' => '',
        'social_whatsapp_url' => '',
        'public_notice' => '',
    ]);

    $socialLinks = collect([
        ['label' => 'Facebook', 'url' => $platformSettings['social_facebook_url'] ?? '', 'icon' => 'facebook'],
        ['label' => 'Instagram', 'url' => $platformSettings['social_instagram_url'] ?? '', 'icon' => 'instagram'],
        ['label' => 'YouTube', 'url' => $platformSettings['social_youtube_url'] ?? '', 'icon' => 'youtube'],
        ['label' => 'LinkedIn', 'url' => $platformSettings['social_linkedin_url'] ?? '', 'icon' => 'linkedin'],
        ['label' => 'WhatsApp', 'url' => $platformSettings['social_whatsapp_url'] ?? '', 'icon' => 'whatsapp'],
    ])->filter(fn (array $link): bool => filled($link['url']))->values();

    $isAdminArea = request()->routeIs('admin.*');
    $footerTheme = $platformSettings['footer_theme'] ?? 'royal';
    $footerClasses = match ($footerTheme) {
        'light' => 'border-t border-white/70 bg-white/95 text-slate-900 shadow-[0_-24px_80px_rgba(148,163,184,0.18)] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950 dark:text-white',
        'midnight' => 'border-t border-slate-800 bg-slate-950 text-white shadow-[0_-24px_80px_rgba(15,23,42,0.35)]',
        default => 'border-t border-blue-900/30 bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 text-white shadow-[0_-24px_90px_rgba(30,64,175,0.28)]',
    };
    $footerMuted = $footerTheme === 'light' ? 'text-slate-600 dark:text-slate-300' : 'text-blue-100/80';
    $footerHeading = $footerTheme === 'light' ? 'text-slate-950 dark:text-white' : 'text-white';
    $footerSubtle = $footerTheme === 'light' ? 'text-slate-500 dark:text-slate-400' : 'text-blue-100/60';
    $footerPanel = $footerTheme === 'light'
        ? 'border border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/70'
        : 'border border-white/10 bg-white/10';
    $footerBottomBorder = $footerTheme === 'light' ? 'border-slate-200/70 dark:border-slate-800' : 'border-white/10';
    $compactFooter = request()->routeIs('payments.*') || request()->routeIs('orders.show') || request()->routeIs('academy-access.*');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $platformSettings['business_tagline'] ?? 'A business platform for ANME Academy training, Easypaisa payments and course operations.' }}">
        <meta name="theme-color" content="#1d4ed8">

        <title>{{ $platformSettings['business_name'] ?? config('app.name', 'ANME LearnOps') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <script>
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <a href="#content" class="skip-link">Skip to content</a>
        <div class="learnops-bg min-h-screen">
            @include('layouts.navigation')

            @if(!$isAdminArea && !empty($platformSettings['public_notice']))
                <div class="border-b border-blue-100 bg-blue-700 text-white dark:border-blue-900">
                    <div class="app-container flex flex-col gap-2 py-3 text-sm font-bold sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ $platformSettings['public_notice'] }}</span>
                        <a href="{{ route('courses.index') }}" class="text-blue-100 underline decoration-2 underline-offset-4">Browse courses</a>
                    </div>
                </div>
            @endif

            @isset($header)
                <header class="border-b border-white/60 bg-white/75 shadow-sm shadow-slate-200/60 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/70 dark:shadow-black/20">
                    <div class="app-container py-7">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="content" tabindex="-1">
                {{ $slot }}
            </main>

            @unless($isAdminArea)
            <footer class="{{ $compactFooter ? 'mt-10' : 'mt-16' }} {{ $footerClasses }}">
                <div class="app-container {{ $compactFooter ? 'py-8' : 'py-12' }}">
                    @unless($compactFooter)
                    <div class="rounded-[2rem] {{ $footerPanel }} p-6 shadow-2xl shadow-slate-950/10 backdrop-blur-xl">
                        <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.28em] {{ $footerSubtle }}">ANME Academy Business Platform</p>
                                <h2 class="mt-3 max-w-3xl text-3xl font-black tracking-[-0.035em] {{ $footerHeading }}">Sell training, manage payments, and activate course access with confidence.</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 {{ $footerMuted }}">{{ $platformSettings['footer_note'] ?? 'The website handles courses, Easypaisa payments, orders, reports and dashboards. ANME Academy stays the student learning experience.' }}</p>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                                <x-ui.button :href="route('courses.index')" variant="orange">Explore Courses</x-ui.button>
                                @if(!empty($platformSettings['academy_url']))
                                    <x-ui.button href="{{ $platformSettings['academy_url'] }}" variant="secondary" target="_blank" rel="noopener">Open Academy</x-ui.button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endunless

                    <div class="{{ $compactFooter ? 'mt-0' : 'mt-10' }} grid gap-8 md:grid-cols-2 xl:grid-cols-[1.35fr_.65fr_.75fr_.8fr]">
                        <div>
                            <div class="flex items-center gap-3">
                                <x-application-logo class="{{ $compactFooter ? 'h-10 w-10' : 'h-12 w-12' }} rounded-2xl shadow-lg shadow-blue-900/20" />
                                <div>
                                    <p class="font-black {{ $footerHeading }}">{{ $platformSettings['business_name'] ?? 'ANME LearnOps' }}</p>
                                    <p class="max-w-sm text-xs font-bold uppercase tracking-widest {{ $footerSubtle }}">{{ $platformSettings['business_tagline'] ?? 'Business + ANME Academy' }}</p>
                                </div>
                            </div>
                            <p class="{{ $compactFooter ? 'mt-3' : 'mt-5' }} max-w-md text-sm leading-6 {{ $footerMuted }}">A professional training commerce layer for course discovery, Easypaisa checkout, student dashboards and academy access operations.</p>

                            @if($socialLinks->isNotEmpty())
                                <div class="mt-6 flex flex-wrap gap-3">
                                    @foreach($socialLinks as $social)
                                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" class="group grid h-11 w-11 place-items-center rounded-2xl border border-white/15 bg-white/10 text-white shadow-lg shadow-slate-950/10 transition hover:-translate-y-1 hover:bg-white hover:text-blue-700 dark:hover:text-blue-700">
                                            <x-ui.icon :name="$social['icon']" class="h-5 w-5" />
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-widest {{ $footerSubtle }}">Platform</p>
                            <div class="mt-4 grid gap-3 text-sm font-bold {{ $footerMuted }}">
                                <a class="transition hover:text-orange-300" href="{{ route('home') }}">Home</a>
                                <a class="transition hover:text-orange-300" href="{{ route('courses.index') }}">Courses</a>
                                <a class="transition hover:text-orange-300" href="{{ route('dashboard') }}">My Learning</a>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-widest {{ $footerSubtle }}">Business</p>
                            <div class="mt-4 grid gap-3 text-sm font-bold {{ $footerMuted }}">
                                <span>{{ $platformSettings['payment_gateway_label'] ?? 'Easypaisa' }} checkout</span>
                                <span>ANME Academy access queue</span>
                                <span>Progress sync reports</span>
                                <span>Currency: {{ $platformSettings['default_currency'] ?? 'PKR' }}</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-widest {{ $footerSubtle }}">Support</p>
                            <div class="mt-4 grid gap-3 text-sm font-bold {{ $footerMuted }}">
                                <a class="break-all transition hover:text-orange-300" href="mailto:{{ $platformSettings['support_email'] ?? 'support@example.com' }}">{{ $platformSettings['support_email'] ?? 'support@example.com' }}</a>
                                <span>{{ $platformSettings['support_phone'] ?? '03000000000' }}</span>
                                @if(!empty($platformSettings['support_whatsapp']))
                                    <span>WhatsApp: {{ $platformSettings['support_whatsapp'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t {{ $footerBottomBorder }} {{ $compactFooter ? 'py-4' : 'py-5' }}">
                    <div class="app-container flex flex-col gap-2 text-xs font-semibold {{ $footerSubtle }} sm:flex-row sm:items-center sm:justify-between">
                        <p>&copy; {{ date('Y') }} {{ $platformSettings['business_name'] ?? config('app.name', 'ANME LearnOps') }}. All rights reserved.</p>
                        <p>Built for premium training operations and secure academy access.</p>
                    </div>
                </div>
            </footer>
            @endunless
        </div>
    </body>
</html>
