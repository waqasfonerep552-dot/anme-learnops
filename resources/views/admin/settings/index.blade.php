@php
    $settingSections = [
        ['id' => 'business', 'label' => 'Business', 'hint' => 'Brand & support', 'icon' => 'home', 'tone' => 'blue'],
        ['id' => 'auth-pages', 'label' => 'Auth Pages', 'hint' => 'Login visuals', 'icon' => 'shield', 'tone' => 'green'],
        ['id' => 'academy', 'label' => 'Academy', 'hint' => 'Student-facing access', 'icon' => 'academy', 'tone' => 'indigo'],
        ['id' => 'payments', 'label' => 'Payments', 'hint' => 'Checkout messages', 'icon' => 'payment', 'tone' => 'orange'],
        ['id' => 'notifications', 'label' => 'Notifications', 'hint' => 'Student updates', 'icon' => 'bell', 'tone' => 'green'],
        ['id' => 'analytics', 'label' => 'Analytics', 'hint' => 'Dashboard charts', 'icon' => 'reports', 'tone' => 'indigo'],
        ['id' => 'footer-social', 'label' => 'Footer & Social', 'hint' => 'Links & theme', 'icon' => 'globe', 'tone' => 'slate'],
    ];

    $manualPaymentReady = filter_var($settings['easypaisa_manual_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)
        && filled($settings['easypaisa_account_title'] ?? null)
        && (filled($settings['easypaisa_till_id'] ?? null) || filled($settings['easypaisa_account_number'] ?? null))
        && (filled($settings['easypaisa_qr_image_path'] ?? null) || filled($settings['easypaisa_qr_image_url'] ?? null));

    $connectionRows = [
        ['Academy Backend URL', $settings['moodle_base_url'], true],
        ['Academy API Token', $settings['moodle_token_configured'] ? 'Configured' : 'Missing', $settings['moodle_token_configured']],
        ['Easypaisa Mode', strtoupper($settings['easypaisa_mode']), true],
        ['Manual Payment Display', $manualPaymentReady ? 'Ready for students' : 'Needs account/Till/QR', $manualPaymentReady],
        ['Easypaisa Store ID', $settings['easypaisa_store_configured'] ? 'Configured' : 'Missing', $settings['easypaisa_store_configured']],
        ['Easypaisa Merchant ID', $settings['easypaisa_merchant_configured'] ? 'Configured' : 'Missing', $settings['easypaisa_merchant_configured']],
        ['Easypaisa Hash Key', $settings['easypaisa_hash_configured'] ? 'Configured' : 'Missing', $settings['easypaisa_hash_configured']],
        ['Webhook Secret', $settings['easypaisa_webhook_secret_configured'] ? 'Configured' : 'Missing', $settings['easypaisa_webhook_secret_configured']],
        ['Checkout URL', $settings['easypaisa_checkout_configured'] ? 'Live checkout configured' : 'Local pending screen', $settings['easypaisa_checkout_configured']],
    ];

    $authUploadedImage = trim((string) ($settings['auth_uploaded_image_path'] ?? ''));
    $authUploadedImageUrl = $authUploadedImage !== '' ? \Illuminate\Support\Facades\Storage::url($authUploadedImage) : null;
    $easypaisaQrImage = trim((string) ($settings['easypaisa_qr_image_path'] ?? ''));
    $easypaisaQrImageUrl = $easypaisaQrImage !== '' ? \Illuminate\Support\Facades\Storage::url($easypaisaQrImage) : null;

    $sectionInputs = [
        'business' => ['business_name', 'business_tagline', 'support_email', 'support_phone', 'support_whatsapp', 'default_currency', 'student_phone_required'],
        'auth-pages' => ['auth_visual_enabled', 'auth_visual_mode', 'auth_image_position', 'auth_image_upload', 'auth_image_url', 'auth_panel_title', 'auth_panel_subtitle'],
        'academy' => ['academy_name', 'academy_access_label', 'academy_url'],
        'payments' => ['payment_gateway_label', 'checkout_button_label', 'easypaisa_manual_enabled', 'easypaisa_account_title', 'easypaisa_account_number', 'easypaisa_till_id', 'easypaisa_qr_image_upload', 'easypaisa_qr_image_url', 'easypaisa_payment_note', 'payment_instructions', 'payment_pending_message', 'payment_success_message', 'fulfilment_retry_instructions'],
        'notifications' => ['student_notifications_enabled', 'moodle_purchase_email_enabled', 'notification_panel_title', 'purchase_email_notice'],
        'analytics' => ['admin_analytics_enabled', 'admin_dashboard_period', 'low_progress_threshold'],
        'footer-social' => ['footer_theme', 'public_notice', 'footer_note', 'social_facebook_url', 'social_instagram_url', 'social_youtube_url', 'social_linkedin_url', 'social_whatsapp_url'],
    ];

    $initialSettingsSection = collect(array_keys($sectionInputs))
        ->first(fn (string $section): bool => collect($sectionInputs[$section])->contains(fn (string $input): bool => $errors->has($input)))
        ?? 'business';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">Settings</p>
                <h1 class="admin-page-title">Admin control center</h1>
                <p class="admin-page-subtitle">Branding, ANME Academy access copy, payment messaging, notifications and integration health — all in one clean panel.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button href="{{ route('home') }}" variant="secondary">Preview Public Site</x-ui.button>
                <x-ui.button href="{{ route('admin.settings.production-readiness') }}" variant="orange">Production Readiness</x-ui.button>
                <x-ui.button href="#integrations" variant="accent">Check Integrations</x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div
                class="min-w-0 space-y-6"
                x-data="{
                    open: '{{ $initialSettingsSection }}',
                    sections: @js(array_keys($sectionInputs)),
                    init() {
                        const hash = window.location.hash.replace('#', '');
                        if (this.sections.includes(hash)) {
                            this.open = hash;
                        }
                    }
                }"
            >
                @if(session('status'))
                    <div class="rounded-3xl border border-green-100 bg-green-50 p-5 font-bold text-green-700 shadow-sm dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                        {{ session('status') }}
                    </div>
                @endif

                <x-ui.card padding="p-5" class="settings-command-center">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-700 dark:text-blue-300">Settings workspace</p>
                            <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Edit one area at a time</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Long form ab compact hai: section choose karo, fields update karo, phir save.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="green">Auto keeps existing values</x-ui.badge>
                            <x-ui.badge variant="blue">One save button</x-ui.badge>
                        </div>
                    </div>

                    <div class="settings-tab-grid mt-5">
                        @foreach($settingSections as $section)
                            <a
                                href="#{{ $section['id'] }}"
                                class="group premium-link-card settings-tab-card"
                                @click.prevent="open = '{{ $section['id'] }}'; history.replaceState(null, '', '#{{ $section['id'] }}')"
                                :class="{ 'settings-tab-card-active': open === '{{ $section['id'] }}' }"
                                :aria-current="open === '{{ $section['id'] }}' ? 'page' : null"
                            >
                                <x-ui.icon-tile :name="$section['icon']" :tone="$section['tone']" size="sm" />
                                <span class="relative z-10 min-w-0">
                                    <span class="text-sm font-black text-slate-950 group-hover:text-blue-700 dark:text-white">{{ $section['label'] }}</span>
                                    <span class="mt-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ $section['hint'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </x-ui.card>

                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] 2xl:grid-cols-[minmax(0,1fr)_24rem]">
                    @csrf
                    @method('PATCH')

                    <div class="min-w-0 space-y-6">
                        <x-ui.card id="business" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'business'" x-transition.opacity.duration.200ms x-cloak>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="eyebrow">Business</p>
                                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Brand & support details</h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Ye values header, footer, checkout aur support areas mein use hoti hain.</p>
                                </div>
                                <x-ui.badge variant="blue">Public</x-ui.badge>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <x-input-label for="business_name" value="Business name" />
                                    <x-text-input id="business_name" name="business_name" class="mt-2 block w-full" value="{{ old('business_name', $settings['business_name']) }}" placeholder="ANME LearnOps" />
                                    <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="business_tagline" value="Business tagline" />
                                    <x-text-input id="business_tagline" name="business_tagline" class="mt-2 block w-full" value="{{ old('business_tagline', $settings['business_tagline']) }}" placeholder="Professional training, payments and academy access in one place" />
                                    <x-input-error :messages="$errors->get('business_tagline')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="support_email" value="Support email" />
                                    <x-text-input id="support_email" name="support_email" type="email" class="mt-2 block w-full" value="{{ old('support_email', $settings['support_email']) }}" placeholder="support@anmeacademy.com" />
                                    <x-input-error :messages="$errors->get('support_email')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="support_phone" value="Support phone" />
                                    <x-text-input id="support_phone" name="support_phone" class="mt-2 block w-full" value="{{ old('support_phone', $settings['support_phone']) }}" placeholder="0300 0000000" />
                                    <x-input-error :messages="$errors->get('support_phone')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="support_whatsapp" value="Support WhatsApp" />
                                    <x-text-input id="support_whatsapp" name="support_whatsapp" class="mt-2 block w-full" value="{{ old('support_whatsapp', $settings['support_whatsapp']) }}" placeholder="0300 0000000" />
                                    <x-input-error :messages="$errors->get('support_whatsapp')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="default_currency" value="Default currency" />
                                    <x-text-input id="default_currency" name="default_currency" maxlength="3" class="mt-2 block w-full uppercase" value="{{ old('default_currency', $settings['default_currency']) }}" placeholder="PKR" />
                                    <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2 rounded-3xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-900/50 dark:bg-blue-950/30">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="student_phone_required" value="0">
                                        <input type="checkbox" name="student_phone_required" value="1" class="mt-1 rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('student_phone_required', $settings['student_phone_required'] ?? '0'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Require student phone at checkout</span>
                                            <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">On karne se checkout par phone/Easypaisa number required hoga; off rahe to optional rahega.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="auth-pages" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'auth-pages'" x-transition.opacity.duration.200ms x-cloak>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="eyebrow">Auth Pages</p>
                                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Login & create account layout</h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Default simple centered form hai. Admin enable kare to split image panel ya full-page background use hoga.</p>
                                </div>
                                <x-ui.badge variant="green">Optional visual</x-ui.badge>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="auth_visual_enabled" value="0">
                                        <input type="checkbox" name="auth_visual_enabled" value="1" class="mt-1 rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('auth_visual_enabled', $settings['auth_visual_enabled'] ?? '0'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Show custom auth visual</span>
                                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Off rahe to login/register page simple centered card rahega.</span>
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <x-input-label for="auth_visual_mode" value="Visual mode" />
                                    <select id="auth_visual_mode" name="auth_visual_mode" class="admin-input mt-2 block w-full">
                                        <option value="split" @selected(old('auth_visual_mode', $settings['auth_visual_mode'] ?? 'split') === 'split')>Split panel image</option>
                                        <option value="background" @selected(old('auth_visual_mode', $settings['auth_visual_mode'] ?? 'split') === 'background')>Full-page background image</option>
                                    </select>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Split mode form ke side par image dikhata hai; background mode poore page par image lagata hai.</p>
                                    <x-input-error :messages="$errors->get('auth_visual_mode')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="auth_image_position" value="Image position" />
                                    <select id="auth_image_position" name="auth_image_position" class="admin-input mt-2 block w-full">
                                        <option value="left" @selected(old('auth_image_position', $settings['auth_image_position'] ?? 'left') === 'left')>Image left, form right</option>
                                        <option value="right" @selected(old('auth_image_position', $settings['auth_image_position'] ?? 'left') === 'right')>Image right, form left</option>
                                    </select>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Ye setting sirf split panel mode mein apply hoti hai.</p>
                                    <x-input-error :messages="$errors->get('auth_image_position')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="auth_image_upload" value="Upload auth image" />
                                    <input id="auth_image_upload" name="auth_image_upload" type="file" accept="image/*" class="admin-input mt-2 block w-full">
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Recommended size: 1600x1000 ya 1920x1080. Max 4MB. Uploaded image URL field se priority le leti hai.</p>
                                    <x-input-error :messages="$errors->get('auth_image_upload')" class="mt-2" />

                                    @if($authUploadedImageUrl)
                                        <div class="mt-4 grid gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40 sm:grid-cols-[8rem,1fr]">
                                            <img src="{{ $authUploadedImageUrl }}" alt="Current auth visual" class="h-28 w-full rounded-2xl object-cover">
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-950 dark:text-white">Current uploaded image</p>
                                                <p class="mt-1 break-all text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $authUploadedImage }}</p>
                                                <label class="mt-3 flex items-start gap-3 text-sm font-bold text-slate-700 dark:text-slate-200">
                                                    <input type="checkbox" name="auth_remove_uploaded_image" value="1" class="mt-1 rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500">
                                                    Remove uploaded image
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="auth_image_url" value="Auth image URL or public path" />
                                    <x-text-input id="auth_image_url" name="auth_image_url" class="mt-2 block w-full" value="{{ old('auth_image_url', $settings['auth_image_url'] ?? '') }}" placeholder="https://example.com/login-image.jpg or images/auth/login.jpg" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Optional fallback/manual image. Public path app ke public folder se resolve hota hai, jaise <code>images/auth/login.jpg</code>.</p>
                                    <x-input-error :messages="$errors->get('auth_image_url')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="auth_panel_title" value="Image panel title" />
                                    <x-text-input id="auth_panel_title" name="auth_panel_title" class="mt-2 block w-full" value="{{ old('auth_panel_title', $settings['auth_panel_title'] ?? 'Welcome to ANME Academy') }}" placeholder="Welcome to ANME Academy" />
                                    <x-input-error :messages="$errors->get('auth_panel_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="auth_panel_subtitle" value="Image panel subtitle" />
                                    <x-text-input id="auth_panel_subtitle" name="auth_panel_subtitle" class="mt-2 block w-full" value="{{ old('auth_panel_subtitle', $settings['auth_panel_subtitle'] ?? '') }}" placeholder="Sign in to continue your learning journey" />
                                    <x-input-error :messages="$errors->get('auth_panel_subtitle')" class="mt-2" />
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="academy" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'academy'" x-transition.opacity.duration.200ms x-cloak>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="eyebrow">Academy</p>
                                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">ANME Academy access copy</h2>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Public user ko backend ka naam nahi dikhana; uske liye destination ANME Academy rahega.</p>
                                </div>
                                <x-ui.badge variant="green">Student-facing</x-ui.badge>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div>
                                    <x-input-label for="academy_name" value="Academy name" />
                                    <x-text-input id="academy_name" name="academy_name" class="mt-2 block w-full" value="{{ old('academy_name', $settings['academy_name'] ?? 'ANME Academy') }}" placeholder="ANME Academy" />
                                    <x-input-error :messages="$errors->get('academy_name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="academy_access_label" value="Academy button label" />
                                    <x-text-input id="academy_access_label" name="academy_access_label" class="mt-2 block w-full" value="{{ old('academy_access_label', $settings['academy_access_label'] ?? 'Open ANME Academy') }}" placeholder="Open ANME Academy" />
                                    <x-input-error :messages="$errors->get('academy_access_label')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="academy_url" value="Academy URL (optional)" />
                                    <x-text-input id="academy_url" name="academy_url" type="url" class="mt-2 block w-full" value="{{ old('academy_url', $settings['academy_url'] ?? '') }}" placeholder="https://academy.example.com" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Agar abhi direct academy URL ready nahi hai to blank chhor dein; dashboard internal course access show karega.</p>
                                    <x-input-error :messages="$errors->get('academy_url')" class="mt-2" />
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="payments" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'payments'" x-transition.opacity.duration.200ms x-cloak>
                            <div>
                                <p class="eyebrow">Payments</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Checkout & payment messages</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Payment journey ki wording admin yahan se control karega.</p>
                            </div>

                            <div class="mt-6 rounded-3xl border {{ $manualPaymentReady ? 'border-green-100 bg-green-50 dark:border-green-900/50 dark:bg-green-950/30' : 'border-orange-100 bg-orange-50 dark:border-orange-900/50 dark:bg-orange-950/30' }} p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex items-start gap-3">
                                        <x-ui.icon-tile :name="$manualPaymentReady ? 'check' : 'payment'" :tone="$manualPaymentReady ? 'green' : 'orange'" size="sm" />
                                        <div>
                                            <h3 class="font-black text-slate-950 dark:text-white">
                                                {{ $manualPaymentReady ? 'Manual Easypaisa display is ready' : 'Manual Easypaisa display needs attention' }}
                                            </h3>
                                            <p class="mt-1 text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                                                Students need account title, Till/account number and QR image before they can confidently pay.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.badge :variant="filled($settings['easypaisa_account_title'] ?? null) ? 'green' : 'orange'">Title</x-ui.badge>
                                        <x-ui.badge :variant="(filled($settings['easypaisa_till_id'] ?? null) || filled($settings['easypaisa_account_number'] ?? null)) ? 'green' : 'orange'">Till / Account</x-ui.badge>
                                        <x-ui.badge :variant="(filled($settings['easypaisa_qr_image_path'] ?? null) || filled($settings['easypaisa_qr_image_url'] ?? null)) ? 'green' : 'orange'">QR</x-ui.badge>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div>
                                    <x-input-label for="payment_gateway_label" value="Payment gateway label" />
                                    <x-text-input id="payment_gateway_label" name="payment_gateway_label" class="mt-2 block w-full" value="{{ old('payment_gateway_label', $settings['payment_gateway_label']) }}" placeholder="Easypaisa" />
                                    <x-input-error :messages="$errors->get('payment_gateway_label')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="checkout_button_label" value="Checkout button label" />
                                    <x-text-input id="checkout_button_label" name="checkout_button_label" class="mt-2 block w-full" value="{{ old('checkout_button_label', $settings['checkout_button_label']) }}" placeholder="Continue to payment" />
                                    <x-input-error :messages="$errors->get('checkout_button_label')" class="mt-2" />
                                </div>

                                <div class="rounded-3xl border border-orange-100 bg-orange-50 p-5 dark:border-orange-900/50 dark:bg-orange-950/30">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="easypaisa_manual_enabled" value="0">
                                        <input type="checkbox" name="easypaisa_manual_enabled" value="1" class="mt-1 rounded border-slate-300 text-orange-600 shadow-sm focus:ring-orange-500" @checked(old('easypaisa_manual_enabled', $settings['easypaisa_manual_enabled'] ?? '1'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Manual Easypaisa verification</span>
                                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">User QR/account par pay karega, receipt upload karega, admin approve karega.</span>
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <x-input-label for="easypaisa_account_title" value="Easypaisa account title" />
                                    <x-text-input id="easypaisa_account_title" name="easypaisa_account_title" class="mt-2 block w-full" value="{{ old('easypaisa_account_title', $settings['easypaisa_account_title'] ?? '') }}" placeholder="ANME Academy / Waqas Habib" />
                                    <x-input-error :messages="$errors->get('easypaisa_account_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="easypaisa_account_number" value="Easypaisa account number" />
                                    <x-text-input id="easypaisa_account_number" name="easypaisa_account_number" class="mt-2 block w-full" value="{{ old('easypaisa_account_number', $settings['easypaisa_account_number'] ?? '') }}" placeholder="03XX XXXXXXX" />
                                    <x-input-error :messages="$errors->get('easypaisa_account_number')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="easypaisa_till_id" value="Easypaisa Till ID / Shop ID" />
                                    <x-text-input id="easypaisa_till_id" name="easypaisa_till_id" class="mt-2 block w-full" value="{{ old('easypaisa_till_id', $settings['easypaisa_till_id'] ?? '') }}" placeholder="Optional Till ID" />
                                    <x-input-error :messages="$errors->get('easypaisa_till_id')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="easypaisa_qr_image_upload" value="Upload Easypaisa QR image" />
                                    <input id="easypaisa_qr_image_upload" name="easypaisa_qr_image_upload" type="file" accept="image/*" class="admin-input mt-2 block w-full">
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">QR image public page par show hoga. Max 4MB.</p>
                                    <x-input-error :messages="$errors->get('easypaisa_qr_image_upload')" class="mt-2" />

                                    @if($easypaisaQrImageUrl)
                                        <div class="mt-4 grid gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40 sm:grid-cols-[8rem,1fr]">
                                            <img src="{{ $easypaisaQrImageUrl }}" alt="Current Easypaisa QR" class="h-28 w-full rounded-2xl object-contain bg-white p-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-950 dark:text-white">Current QR image</p>
                                                <p class="mt-1 break-all text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $easypaisaQrImage }}</p>
                                                <label class="mt-3 flex items-start gap-3 text-sm font-bold text-slate-700 dark:text-slate-200">
                                                    <input type="checkbox" name="easypaisa_remove_qr_image" value="1" class="mt-1 rounded border-slate-300 text-red-600 shadow-sm focus:ring-red-500">
                                                    Remove QR image
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="easypaisa_qr_image_url" value="Easypaisa QR image URL or public path" />
                                    <x-text-input id="easypaisa_qr_image_url" name="easypaisa_qr_image_url" class="mt-2 block w-full" value="{{ old('easypaisa_qr_image_url', $settings['easypaisa_qr_image_url'] ?? '') }}" placeholder="https://example.com/easypaisa-qr.png or images/payments/easypaisa-qr.png" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Optional fallback. Uploaded QR image is used first.</p>
                                    <x-input-error :messages="$errors->get('easypaisa_qr_image_url')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="easypaisa_payment_note" value="Manual Easypaisa instructions" />
                                    <textarea id="easypaisa_payment_note" name="easypaisa_payment_note" rows="3" class="admin-input mt-2 block w-full" placeholder="Tell students how to pay and upload proof">{{ old('easypaisa_payment_note', $settings['easypaisa_payment_note'] ?? '') }}</textarea>
                                    <x-input-error :messages="$errors->get('easypaisa_payment_note')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="payment_instructions" value="Checkout payment instructions" />
                                    <textarea id="payment_instructions" name="payment_instructions" rows="4" class="admin-input mt-2 block w-full" placeholder="Explain what happens after payment confirmation">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea>
                                    <x-input-error :messages="$errors->get('payment_instructions')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="payment_pending_message" value="Payment pending message" />
                                    <textarea id="payment_pending_message" name="payment_pending_message" rows="3" class="admin-input mt-2 block w-full" placeholder="Message shown while payment is pending">{{ old('payment_pending_message', $settings['payment_pending_message']) }}</textarea>
                                    <x-input-error :messages="$errors->get('payment_pending_message')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="payment_success_message" value="Payment success message" />
                                    <textarea id="payment_success_message" name="payment_success_message" rows="3" class="admin-input mt-2 block w-full" placeholder="Message shown after payment confirmation">{{ old('payment_success_message', $settings['payment_success_message']) }}</textarea>
                                    <x-input-error :messages="$errors->get('payment_success_message')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="fulfilment_retry_instructions" value="Fulfilment retry instructions" />
                                    <textarea id="fulfilment_retry_instructions" name="fulfilment_retry_instructions" rows="3" class="admin-input mt-2 block w-full" placeholder="Admin guidance when academy access needs retry">{{ old('fulfilment_retry_instructions', $settings['fulfilment_retry_instructions']) }}</textarea>
                                    <x-input-error :messages="$errors->get('fulfilment_retry_instructions')" class="mt-2" />
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="notifications" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'notifications'" x-transition.opacity.duration.200ms x-cloak>
                            <div>
                                <p class="eyebrow">Notifications</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Student updates & email notices</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Dashboard notification aur academy confirmation email ki copy/control yahan hai.</p>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="student_notifications_enabled" value="0">
                                        <input type="checkbox" name="student_notifications_enabled" value="1" class="mt-1 rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('student_notifications_enabled', $settings['student_notifications_enabled'] ?? '1'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Student dashboard notifications</span>
                                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Paid enrolment complete hone ke baad student dashboard par access-ready update show hoga.</span>
                                        </span>
                                    </label>
                                </div>

                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="moodle_purchase_email_enabled" value="0">
                                        <input type="checkbox" name="moodle_purchase_email_enabled" value="1" class="mt-1 rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('moodle_purchase_email_enabled', $settings['moodle_purchase_email_enabled'] ?? '1'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Academy confirmation email</span>
                                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Course access active hone ke baad confirmation email academy mail system se send hoga.</span>
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <x-input-label for="notification_panel_title" value="Notification panel title" />
                                    <x-text-input id="notification_panel_title" name="notification_panel_title" class="mt-2 block w-full" value="{{ old('notification_panel_title', $settings['notification_panel_title']) }}" placeholder="Latest updates" />
                                    <x-input-error :messages="$errors->get('notification_panel_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="purchase_email_notice" value="Purchase email notice" />
                                    <x-text-input id="purchase_email_notice" name="purchase_email_notice" class="mt-2 block w-full" value="{{ old('purchase_email_notice', $settings['purchase_email_notice']) }}" placeholder="Confirmation email is sent after paid enrolment is activated" />
                                    <x-input-error :messages="$errors->get('purchase_email_notice')" class="mt-2" />
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="analytics" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'analytics'" x-transition.opacity.duration.200ms x-cloak>
                            <div>
                                <p class="eyebrow">Analytics</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Dashboard charts & progress alerts</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Admin dashboard par revenue trend, progress bands aur low-progress watchlist ka control yahan hai.</p>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                    <label class="flex items-start gap-3">
                                        <input type="hidden" name="admin_analytics_enabled" value="0">
                                        <input type="checkbox" name="admin_analytics_enabled" value="1" class="mt-1 rounded border-slate-300 text-blue-700 shadow-sm focus:ring-blue-600" @checked(old('admin_analytics_enabled', $settings['admin_analytics_enabled'] ?? '1'))>
                                        <span>
                                            <span class="block font-black text-slate-950 dark:text-white">Show admin analytics panel</span>
                                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Revenue aur learner progress charts admin dashboard par visible rahenge.</span>
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <x-input-label for="admin_dashboard_period" value="Dashboard reporting period" />
                                    <select id="admin_dashboard_period" name="admin_dashboard_period" class="admin-input mt-2 block w-full">
                                        @foreach([30 => 'Last 30 days', 90 => 'Last 90 days', 180 => 'Last 180 days', 365 => 'Last 12 months'] as $value => $label)
                                            <option value="{{ $value }}" @selected((int) old('admin_dashboard_period', $settings['admin_dashboard_period'] ?? 90) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('admin_dashboard_period')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="low_progress_threshold" value="Low progress alert threshold" />
                                    <x-text-input id="low_progress_threshold" name="low_progress_threshold" type="number" min="0" max="100" class="mt-2 block w-full" value="{{ old('low_progress_threshold', $settings['low_progress_threshold'] ?? '35') }}" placeholder="35" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Is percentage se neeche learners support watchlist mein count honge.</p>
                                    <x-input-error :messages="$errors->get('low_progress_threshold')" class="mt-2" />
                                </div>

                                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-900/50 dark:bg-blue-950/30">
                                    <div class="flex items-start gap-3">
                                        <x-ui.icon-tile name="reports" tone="blue" size="sm" />
                                        <div>
                                            <p class="font-black text-slate-950 dark:text-white">Progress sync reminder</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">Charts tabhi meaningful honge jab scheduled progress sync regularly chal raha ho.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card id="footer-social" padding="p-7" class="settings-panel scroll-mt-28" x-show="open === 'footer-social'" x-transition.opacity.duration.200ms x-cloak>
                            <div>
                                <p class="eyebrow">Footer & Social</p>
                                <h2 class="text-2xl font-black text-slate-950 dark:text-white">Footer theme, notice & social links</h2>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Admin yahan se footer background, public notice aur social profile links manage karega.</p>
                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">
                                <div>
                                    <x-input-label for="footer_theme" value="Footer background style" />
                                    <select id="footer_theme" name="footer_theme" class="admin-input mt-2 block w-full">
                                        <option value="royal" @selected(old('footer_theme', $settings['footer_theme'] ?? 'royal') === 'royal')>Royal blue gradient</option>
                                        <option value="midnight" @selected(old('footer_theme', $settings['footer_theme'] ?? 'royal') === 'midnight')>Midnight dark</option>
                                        <option value="light" @selected(old('footer_theme', $settings['footer_theme'] ?? 'royal') === 'light')>Clean light</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('footer_theme')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="public_notice" value="Public notice banner" />
                                    <x-text-input id="public_notice" name="public_notice" class="mt-2 block w-full" value="{{ old('public_notice', $settings['public_notice']) }}" placeholder="Optional announcement shown above the page content" />
                                    <x-input-error :messages="$errors->get('public_notice')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="footer_note" value="Footer note" />
                                    <textarea id="footer_note" name="footer_note" rows="3" class="admin-input mt-2 block w-full" placeholder="Short professional footer note">{{ old('footer_note', $settings['footer_note']) }}</textarea>
                                    <x-input-error :messages="$errors->get('footer_note')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="social_facebook_url" value="Facebook URL" />
                                    <x-text-input id="social_facebook_url" name="social_facebook_url" type="url" class="mt-2 block w-full" value="{{ old('social_facebook_url', $settings['social_facebook_url'] ?? '') }}" placeholder="https://facebook.com/your-page" />
                                    <x-input-error :messages="$errors->get('social_facebook_url')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="social_instagram_url" value="Instagram URL" />
                                    <x-text-input id="social_instagram_url" name="social_instagram_url" type="url" class="mt-2 block w-full" value="{{ old('social_instagram_url', $settings['social_instagram_url'] ?? '') }}" placeholder="https://instagram.com/your-page" />
                                    <x-input-error :messages="$errors->get('social_instagram_url')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="social_youtube_url" value="YouTube URL" />
                                    <x-text-input id="social_youtube_url" name="social_youtube_url" type="url" class="mt-2 block w-full" value="{{ old('social_youtube_url', $settings['social_youtube_url'] ?? '') }}" placeholder="https://youtube.com/@your-channel" />
                                    <x-input-error :messages="$errors->get('social_youtube_url')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="social_linkedin_url" value="LinkedIn URL" />
                                    <x-text-input id="social_linkedin_url" name="social_linkedin_url" type="url" class="mt-2 block w-full" value="{{ old('social_linkedin_url', $settings['social_linkedin_url'] ?? '') }}" placeholder="https://linkedin.com/company/your-company" />
                                    <x-input-error :messages="$errors->get('social_linkedin_url')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="social_whatsapp_url" value="WhatsApp link" />
                                    <x-text-input id="social_whatsapp_url" name="social_whatsapp_url" type="url" class="mt-2 block w-full" value="{{ old('social_whatsapp_url', $settings['social_whatsapp_url'] ?? '') }}" placeholder="https://wa.me/923000000000" />
                                    <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Blank chhorne par footer WhatsApp social button hide rahega.</p>
                                    <x-input-error :messages="$errors->get('social_whatsapp_url')" class="mt-2" />
                                </div>
                            </div>
                        </x-ui.card>

                        <div class="sticky bottom-4 z-20 rounded-3xl border border-white/70 bg-white/90 p-4 shadow-2xl shadow-blue-900/10 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="max-w-lg text-sm font-bold leading-6 text-slate-600 dark:text-slate-300">Changes save karne ke baad public pages instantly new copy use karenge.</p>
                                <div class="flex shrink-0 flex-wrap gap-3">
                                    <x-ui.button type="submit">
                                        <x-ui.icon name="check" class="h-4 w-4" />
                                        Save Settings
                                    </x-ui.button>
                                    <x-ui.button href="#business" variant="secondary">Back to Top</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside id="integrations" class="min-w-0 space-y-6 scroll-mt-28 xl:sticky xl:top-28 xl:self-start">
                        <x-ui.card dark>
                            <p class="text-xs font-black uppercase tracking-widest text-blue-200">Secure Integrations</p>
                            <h2 class="mt-3 text-3xl font-black">Connection health</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Secrets `.env` mein safe rahte hain; yahan sirf configured/missing status show hota hai.</p>

                            <div class="mt-6 space-y-4 text-sm">
                                @foreach($connectionRows as $row)
                                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 shadow-lg shadow-slate-950/10 transition hover:bg-white/10">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $row[2] ? 'bg-green-400/15 text-green-300' : 'bg-orange-400/15 text-orange-300' }}">
                                                <x-ui.icon :name="$row[2] ? 'check' : 'settings'" class="h-5 w-5" />
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <dt class="font-bold text-slate-300">{{ $row[0] }}</dt>
                                                <dd class="mt-2 break-all font-black text-white">{{ $row[1] }}</dd>
                                            </div>
                                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black uppercase {{ $row[2] ? 'bg-green-400/15 text-green-300' : 'bg-orange-400/15 text-orange-300' }}">
                                                {{ $row[2] ? 'OK' : 'Check' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <x-ui.button href="{{ route('admin.settings.moodle-diagnostics') }}" variant="secondary" class="mt-6 w-full">
                                <x-ui.icon name="shield" class="h-4 w-4" />
                                Test Academy API
                            </x-ui.button>
                            <x-ui.button href="{{ route('admin.settings.production-readiness') }}" variant="orange" class="mt-3 w-full">
                                <x-ui.icon name="settings" class="h-4 w-4" />
                                Production Readiness
                            </x-ui.button>
                        </x-ui.card>

                        <x-ui.card>
                            <div class="flex items-start gap-3">
                                <x-ui.icon-tile name="shield" tone="green" size="sm" />
                                <div>
                                    <h3 class="text-xl font-black text-slate-950 dark:text-white">Important security note</h3>
                                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">API token, Easypaisa merchant ID aur hash key security-sensitive hain. Admin UI unko edit/display nahi karta; production secrets `.env` mein hi rahenge.</p>
                                </div>
                            </div>
                        </x-ui.card>
                    </aside>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
