@php
    $platformSettings = \App\Models\PlatformSetting::publicValues([
        'business_name' => config('app.name', 'ANME LearnOps'),
        'business_tagline' => 'Training commerce suite',
        'auth_visual_enabled' => '0',
        'auth_visual_mode' => 'split',
        'auth_image_url' => '',
        'auth_uploaded_image_path' => '',
        'auth_image_position' => 'left',
        'auth_panel_title' => 'Welcome to ANME Academy',
        'auth_panel_subtitle' => 'Sign in to continue your learning, payments and course access journey.',
    ]);
    $authVisualEnabled = filter_var($platformSettings['auth_visual_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $authVisualMode = ($platformSettings['auth_visual_mode'] ?? 'split') === 'background' ? 'background' : 'split';
    $uploadedAuthImage = trim((string) ($platformSettings['auth_uploaded_image_path'] ?? ''));
    $manualAuthImage = trim((string) ($platformSettings['auth_image_url'] ?? ''));
    $authImage = $uploadedAuthImage !== '' ? $uploadedAuthImage : $manualAuthImage;
    $hasAuthVisual = $authVisualEnabled && $authImage !== '';
    $authImageSrc = '';

    if ($hasAuthVisual) {
        $authImageSrc = $uploadedAuthImage !== ''
            ? \Illuminate\Support\Facades\Storage::url($uploadedAuthImage)
            : (\Illuminate\Support\Str::startsWith($manualAuthImage, ['http://', 'https://', '/']) ? $manualAuthImage : asset($manualAuthImage));
    }

    $isBackgroundVisual = $hasAuthVisual && $authVisualMode === 'background';
    $isSplitVisual = $hasAuthVisual && ! $isBackgroundVisual;
    $imageOnRight = ($platformSettings['auth_image_position'] ?? 'left') === 'right';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $platformSettings['business_tagline'] ?? 'Training commerce suite' }}">
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
        <main
            id="content"
            class="relative min-h-screen overflow-hidden text-slate-950 dark:text-white {{ $isBackgroundVisual ? 'bg-cover bg-center bg-fixed' : 'bg-slate-50 dark:bg-slate-950' }}"
            @if($isBackgroundVisual) style="background-image: url('{{ $authImageSrc }}');" @endif
            tabindex="-1"
        >
            @if($isBackgroundVisual)
                <div class="absolute inset-0 bg-slate-950/50 dark:bg-slate-950/70"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,.32),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(124,58,237,.26),transparent_36%)]"></div>
            @endif

            <div class="app-container relative z-10 flex min-h-screen items-center justify-center py-8">
                <div class="w-full {{ $isSplitVisual ? 'max-w-6xl' : 'max-w-md' }}">
                    <div class="mb-7 flex items-center justify-between gap-4">
                        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                            <x-application-logo class="h-11 w-11 shrink-0 rounded-2xl" />
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-black tracking-tight {{ $isBackgroundVisual ? 'text-white' : 'text-slate-950 dark:text-white' }}">{{ $platformSettings['business_name'] ?? 'ANME LearnOps' }}</span>
                                <span class="block truncate text-[0.68rem] font-bold uppercase tracking-[0.22em] {{ $isBackgroundVisual ? 'text-blue-100/80' : 'text-slate-400' }}">{{ $platformSettings['business_tagline'] ?? 'Training commerce suite' }}</span>
                            </span>
                        </a>
                        <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="premium-icon-button group {{ $isBackgroundVisual ? 'border-white/20 bg-white/15 text-white backdrop-blur-xl hover:bg-white/25' : '' }}" aria-label="Toggle dark mode">
                            <x-ui.icon name="moon" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="{{ $isSplitVisual ? 'grid overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-blue-950/10 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 lg:grid-cols-2' : '' }}">
                        @if($isSplitVisual)
                            <section class="{{ $imageOnRight ? 'lg:order-2' : '' }} hidden min-h-[34rem] bg-cover bg-center lg:block" style="background-image: linear-gradient(180deg, rgba(15,23,42,.18), rgba(15,23,42,.68)), url('{{ $authImageSrc }}');">
                                <div class="flex h-full items-end p-10 text-white">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.3em] text-blue-100">ANME Academy</p>
                                        <h1 class="mt-3 max-w-md text-4xl font-black tracking-[-0.04em]">{{ $platformSettings['auth_panel_title'] }}</h1>
                                        <p class="mt-4 max-w-md text-sm font-semibold leading-6 text-blue-50">{{ $platformSettings['auth_panel_subtitle'] }}</p>
                                    </div>
                                </div>
                            </section>
                        @endif

                        <section class="{{
                            $isSplitVisual
                                ? 'p-6 sm:p-10 lg:p-12'
                                : ($isBackgroundVisual
                                    ? 'rounded-[2rem] bg-white/95 p-7 shadow-2xl shadow-slate-950/30 ring-1 ring-white/60 backdrop-blur-xl dark:bg-slate-950/90 dark:ring-white/10 sm:p-9'
                                    : 'rounded-[2rem] bg-white p-7 shadow-xl shadow-blue-950/10 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-9')
                        }}">
                            {{ $slot }}
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
