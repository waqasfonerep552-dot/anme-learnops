<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Services\MoodleConnectionTester;
use App\Services\MoodleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * @return array<string, string|null>
     */
    private function defaults(): array
    {
        // Defaults tab use hote hain jab admin ne settings abhi save na ki hon.
        return [
            'business_name' => config('app.name'),
            'business_tagline' => 'Professional training, payments and ANME Academy access in one place.',
            'academy_name' => 'ANME Academy',
            'academy_url' => '',
            'academy_access_label' => 'Open ANME Academy',
            'support_email' => 'support@example.com',
            'support_phone' => '03000000000',
            'support_whatsapp' => '03000000000',
            'default_currency' => 'PKR',
            'student_phone_required' => '0',
            'payment_gateway_label' => 'Easypaisa',
            'checkout_button_label' => 'Continue to payment',
            'easypaisa_manual_enabled' => '1',
            'easypaisa_account_title' => '',
            'easypaisa_account_number' => '',
            'easypaisa_till_id' => '',
            'easypaisa_qr_image_url' => '',
            'easypaisa_qr_image_path' => '',
            'easypaisa_payment_note' => 'Scan the QR code or transfer to the Easypaisa account, then upload your receipt for admin verification.',
            'payment_instructions' => 'After Easypaisa payment is confirmed, your ANME Academy course access will be activated automatically.',
            'payment_pending_message' => 'Your payment is pending. Once payment is confirmed, ANME Academy access will be processed automatically.',
            'payment_success_message' => 'Payment confirmed. ANME Academy access is being prepared in the background.',
            'fulfilment_retry_instructions' => 'If academy access fails after a paid order, use retry fulfilment from the admin orders screen.',
            'student_notifications_enabled' => '1',
            'moodle_purchase_email_enabled' => '1',
            'notification_panel_title' => 'Latest updates',
            'purchase_email_notice' => 'A confirmation email is sent after paid enrolment is activated.',
            'admin_analytics_enabled' => '1',
            'admin_dashboard_period' => '90',
            'low_progress_threshold' => '35',
            'auth_visual_enabled' => '0',
            'auth_visual_mode' => 'split',
            'auth_image_url' => '',
            'auth_uploaded_image_path' => '',
            'auth_image_position' => 'left',
            'auth_panel_title' => 'Welcome to ANME Academy',
            'auth_panel_subtitle' => 'Sign in to continue your learning, payments and course access journey.',
            'footer_note' => 'Production-ready foundation for ANME Academy training operations.',
            'footer_theme' => 'royal',
            'social_facebook_url' => '',
            'social_instagram_url' => '',
            'social_youtube_url' => '',
            'social_linkedin_url' => '',
            'social_whatsapp_url' => '',
            'public_notice' => '',
        ];
    }

    public function index(): View
    {
        // Public settings DB se, sensitive integration status config/.env se aata hai.
        return view('admin.settings.index', [
            'settings' => PlatformSetting::publicValues($this->defaults()) + [
                'moodle_base_url' => config('moodle.base_url'),
                'easypaisa_mode' => config('easypaisa.mode'),
                'moodle_token_configured' => filled(config('moodle.token')),
                'easypaisa_store_configured' => filled(config('easypaisa.store_id')),
                'easypaisa_merchant_configured' => filled(config('easypaisa.merchant_id')),
                'easypaisa_hash_configured' => filled(config('easypaisa.hash_key')),
                'easypaisa_checkout_configured' => filled(config('easypaisa.checkout_url')),
                'easypaisa_webhook_secret_configured' => filled(config('easypaisa.webhook_secret')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Admin editable business copy/support details validate karte hain.
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:120'],
            'business_tagline' => ['nullable', 'string', 'max:180'],
            'academy_name' => ['nullable', 'string', 'max:120'],
            'academy_url' => ['nullable', 'url', 'max:255'],
            'academy_access_label' => ['nullable', 'string', 'max:80'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'student_phone_required' => ['nullable', 'boolean'],
            'payment_gateway_label' => ['nullable', 'string', 'max:80'],
            'checkout_button_label' => ['nullable', 'string', 'max:80'],
            'easypaisa_manual_enabled' => ['nullable', 'boolean'],
            'easypaisa_account_title' => ['nullable', 'string', 'max:120'],
            'easypaisa_account_number' => ['nullable', 'string', 'max:40'],
            'easypaisa_till_id' => ['nullable', 'string', 'max:60'],
            'easypaisa_qr_image_url' => ['nullable', 'string', 'max:500'],
            'easypaisa_qr_image_upload' => ['nullable', 'image', 'max:4096'],
            'easypaisa_remove_qr_image' => ['nullable', 'boolean'],
            'easypaisa_payment_note' => ['nullable', 'string', 'max:500'],
            'payment_instructions' => ['nullable', 'string', 'max:800'],
            'payment_pending_message' => ['nullable', 'string', 'max:500'],
            'payment_success_message' => ['nullable', 'string', 'max:500'],
            'fulfilment_retry_instructions' => ['nullable', 'string', 'max:500'],
            'student_notifications_enabled' => ['nullable', 'boolean'],
            'moodle_purchase_email_enabled' => ['nullable', 'boolean'],
            'notification_panel_title' => ['nullable', 'string', 'max:80'],
            'purchase_email_notice' => ['nullable', 'string', 'max:400'],
            'admin_analytics_enabled' => ['nullable', 'boolean'],
            'admin_dashboard_period' => ['nullable', 'integer', 'in:30,90,180,365'],
            'low_progress_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auth_visual_enabled' => ['nullable', 'boolean'],
            'auth_visual_mode' => ['nullable', 'in:split,background'],
            'auth_image_url' => ['nullable', 'string', 'max:500'],
            'auth_image_upload' => ['nullable', 'image', 'max:4096'],
            'auth_remove_uploaded_image' => ['nullable', 'boolean'],
            'auth_image_position' => ['nullable', 'in:left,right'],
            'auth_panel_title' => ['nullable', 'string', 'max:120'],
            'auth_panel_subtitle' => ['nullable', 'string', 'max:240'],
            'footer_note' => ['nullable', 'string', 'max:300'],
            'footer_theme' => ['nullable', 'in:royal,midnight,light'],
            'social_facebook_url' => ['nullable', 'url', 'max:255'],
            'social_instagram_url' => ['nullable', 'url', 'max:255'],
            'social_youtube_url' => ['nullable', 'url', 'max:255'],
            'social_linkedin_url' => ['nullable', 'url', 'max:255'],
            'social_whatsapp_url' => ['nullable', 'url', 'max:255'],
            'public_notice' => ['nullable', 'string', 'max:220'],
        ]);

        $data['student_notifications_enabled'] = $request->boolean('student_notifications_enabled') ? '1' : '0';
        $data['moodle_purchase_email_enabled'] = $request->boolean('moodle_purchase_email_enabled') ? '1' : '0';
        $data['admin_analytics_enabled'] = $request->boolean('admin_analytics_enabled') ? '1' : '0';
        $data['student_phone_required'] = $request->boolean('student_phone_required') ? '1' : '0';
        $data['auth_visual_enabled'] = $request->boolean('auth_visual_enabled') ? '1' : '0';
        $data['auth_visual_mode'] = filled($data['auth_visual_mode'] ?? null) ? $data['auth_visual_mode'] : 'split';
        $data['business_name'] = filled($data['business_name'] ?? null) ? $data['business_name'] : config('app.name', 'ANME LearnOps');
        $data['academy_name'] = filled($data['academy_name'] ?? null) ? $data['academy_name'] : 'ANME Academy';
        $data['academy_access_label'] = filled($data['academy_access_label'] ?? null) ? $data['academy_access_label'] : 'Open ANME Academy';
        $data['support_email'] = filled($data['support_email'] ?? null) ? $data['support_email'] : 'support@example.com';
        $data['default_currency'] = filled($data['default_currency'] ?? null) ? strtoupper($data['default_currency']) : 'PKR';
        $data['payment_gateway_label'] = filled($data['payment_gateway_label'] ?? null) ? $data['payment_gateway_label'] : 'Easypaisa';
        $data['checkout_button_label'] = filled($data['checkout_button_label'] ?? null) ? $data['checkout_button_label'] : 'Continue to payment';
        $data['easypaisa_manual_enabled'] = $request->boolean('easypaisa_manual_enabled') ? '1' : '0';
        $data['easypaisa_payment_note'] = filled($data['easypaisa_payment_note'] ?? null)
            ? $data['easypaisa_payment_note']
            : 'Scan the QR code or transfer to the Easypaisa account, then upload your receipt for admin verification.';
        $data['admin_dashboard_period'] = filled($data['admin_dashboard_period'] ?? null) ? (string) $data['admin_dashboard_period'] : '90';
        $data['low_progress_threshold'] = filled($data['low_progress_threshold'] ?? null) ? (string) $data['low_progress_threshold'] : '35';
        $data['auth_image_position'] = filled($data['auth_image_position'] ?? null) ? $data['auth_image_position'] : 'left';
        $data['auth_panel_title'] = filled($data['auth_panel_title'] ?? null) ? $data['auth_panel_title'] : 'Welcome to ANME Academy';
        $data['auth_panel_subtitle'] = filled($data['auth_panel_subtitle'] ?? null) ? $data['auth_panel_subtitle'] : 'Sign in to continue your learning, payments and course access journey.';
        $data['footer_theme'] = filled($data['footer_theme'] ?? null) ? $data['footer_theme'] : 'royal';

        $currentAuthImagePath = PlatformSetting::getValue('auth_uploaded_image_path');

        if ($request->boolean('auth_remove_uploaded_image')) {
            if (filled($currentAuthImagePath) && Storage::disk('public')->exists($currentAuthImagePath)) {
                Storage::disk('public')->delete($currentAuthImagePath);
            }

            $data['auth_uploaded_image_path'] = '';
        }

        if ($request->hasFile('auth_image_upload')) {
            if (filled($currentAuthImagePath) && Storage::disk('public')->exists($currentAuthImagePath)) {
                Storage::disk('public')->delete($currentAuthImagePath);
            }

            $data['auth_uploaded_image_path'] = $request->file('auth_image_upload')->store('auth-pages', 'public');
        }

        $currentQrImagePath = PlatformSetting::getValue('easypaisa_qr_image_path');

        if ($request->boolean('easypaisa_remove_qr_image')) {
            if (filled($currentQrImagePath) && Storage::disk('public')->exists($currentQrImagePath)) {
                Storage::disk('public')->delete($currentQrImagePath);
            }

            $data['easypaisa_qr_image_path'] = '';
        }

        if ($request->hasFile('easypaisa_qr_image_upload')) {
            if (filled($currentQrImagePath) && Storage::disk('public')->exists($currentQrImagePath)) {
                Storage::disk('public')->delete($currentQrImagePath);
            }

            $data['easypaisa_qr_image_path'] = $request->file('easypaisa_qr_image_upload')->store('easypaisa-qr', 'public');
        }

        unset(
            $data['auth_image_upload'],
            $data['auth_remove_uploaded_image'],
            $data['easypaisa_qr_image_upload'],
            $data['easypaisa_remove_qr_image']
        );

        foreach ($data as $key => $value) {
            PlatformSetting::setValue($key, is_string($value) ? trim($value) : $value);
        }

        return back()->with('status', 'Settings updated successfully.');
    }

    public function moodleDiagnostics(MoodleService $moodle, MoodleConnectionTester $tester): View
    {
        // Diagnostics page token aur allowed functions ka safe health-check dikhata hai.
        return view('admin.settings.moodle-diagnostics', [
            'baseUrl' => config('moodle.base_url'),
            'restUrl' => config('moodle.rest_url'),
            'tokenConfigured' => filled(config('moodle.token')),
            'functions' => config('moodle.functions'),
            'checks' => filled(config('moodle.token')) ? $tester->run($moodle) : [],
        ]);
    }

    public function productionReadiness(): View
    {
        $sections = $this->productionReadinessSections();
        $flatChecks = collect($sections)->flatMap(fn (array $section) => $section['checks']);

        return view('admin.settings.production-readiness', [
            'sections' => $sections,
            'summary' => [
                'ok' => $flatChecks->where('status', 'ok')->count(),
                'warn' => $flatChecks->where('status', 'warn')->count(),
                'danger' => $flatChecks->where('status', 'danger')->count(),
                'total' => $flatChecks->count(),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productionReadinessSections(): array
    {
        $appUrl = (string) config('app.url');
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;
        $pendingReviews = Payment::where('status', 'pending_verification')->count();
        $failedEnrollments = Enrollment::where('status', 'failed')->count();

        return [
            [
                'title' => 'Application',
                'hint' => 'Core Laravel production flags.',
                'icon' => 'settings',
                'checks' => [
                    $this->readinessCheck('Environment', config('app.env'), app()->environment('production') ? 'ok' : 'warn', 'Production hosting should use APP_ENV=production.'),
                    $this->readinessCheck('Debug mode', config('app.debug') ? 'ON' : 'OFF', config('app.debug') ? 'danger' : 'ok', 'APP_DEBUG=false protects stack traces and sensitive error details.'),
                    $this->readinessCheck('App key', filled(config('app.key')) ? 'Configured' : 'Missing', filled(config('app.key')) ? 'ok' : 'danger', 'APP_KEY is required for encrypted cookies and data.'),
                    $this->readinessCheck('HTTPS app URL', $appUrl, str_starts_with($appUrl, 'https://') ? 'ok' : 'warn', 'Use an HTTPS APP_URL for production domains.'),
                    $this->readinessCheck('Config cache', app()->configurationIsCached() ? 'Cached' : 'Not cached', app()->environment('production') && ! app()->configurationIsCached() ? 'warn' : 'ok', 'Run php artisan config:cache during deployment.'),
                    $this->readinessCheck('Route cache', app()->routesAreCached() ? 'Cached' : 'Not cached', app()->environment('production') && ! app()->routesAreCached() ? 'warn' : 'ok', 'Run php artisan route:cache during deployment after routes are final.'),
                ],
            ],
            [
                'title' => 'Storage & Queue',
                'hint' => 'Fulfilment depends on queue workers and private receipt storage.',
                'icon' => 'orders',
                'checks' => [
                    $this->readinessCheck('Database', config('database.default'), config('database.default') === 'sqlite' ? 'warn' : 'ok', 'Use MySQL/MariaDB/PostgreSQL for production traffic.'),
                    $this->readinessCheck('Queue driver', config('queue.default'), config('queue.default') === 'sync' ? 'danger' : 'ok', 'Moodle fulfilment should run through a persistent queue worker.'),
                    $this->readinessCheck('Session driver', config('session.driver'), config('session.driver') === 'array' ? 'danger' : 'ok', 'Production sessions should persist between requests.'),
                    $this->readinessCheck('Public storage link', is_dir(public_path('storage')) ? 'Linked' : 'Missing', is_dir(public_path('storage')) ? 'ok' : 'danger', 'Run php artisan storage:link for public QR/auth/course images.'),
                    $this->readinessCheck('Private receipt storage', is_writable(storage_path('app/private')) ? 'Writable' : 'Not writable', is_writable(storage_path('app/private')) ? 'ok' : 'danger', 'Manual payment receipts are stored outside public web access.'),
                    $this->readinessCheck('Queued jobs', $pendingJobs === null ? 'Table missing' : (string) $pendingJobs, $pendingJobs === null ? 'danger' : ($pendingJobs > 25 ? 'warn' : 'ok'), 'Large queue buildup means the queue worker may be stopped.'),
                    $this->readinessCheck('Failed jobs', $failedJobs === null ? 'Table missing' : (string) $failedJobs, $failedJobs === null || $failedJobs > 0 ? 'danger' : 'ok', 'Failed jobs should be reviewed before launch.'),
                ],
            ],
            [
                'title' => 'Payments',
                'hint' => 'Easypaisa callbacks must be verifiable before activating access.',
                'icon' => 'payment',
                'checks' => [
                    $this->readinessCheck('Easypaisa mode', strtoupper((string) config('easypaisa.mode')), config('easypaisa.mode') === 'production' ? 'ok' : 'warn', 'Switch to production mode when live credentials are ready.'),
                    $this->readinessCheck('Store ID', filled(config('easypaisa.store_id')) ? 'Configured' : 'Missing', filled(config('easypaisa.store_id')) ? 'ok' : 'warn', 'Required for live hosted checkout.'),
                    $this->readinessCheck('Merchant ID', filled(config('easypaisa.merchant_id')) ? 'Configured' : 'Missing', filled(config('easypaisa.merchant_id')) ? 'ok' : 'warn', 'Required for live settlement/account mapping.'),
                    $this->readinessCheck('Hash key', filled(config('easypaisa.hash_key')) ? 'Configured' : 'Missing', filled(config('easypaisa.hash_key')) ? 'ok' : 'danger', 'Webhook signatures are enforced when EASYPAISA_HASH_KEY is configured.'),
                    $this->readinessCheck('Webhook secret', filled(config('easypaisa.webhook_secret')) ? 'Configured' : 'Missing', filled(config('easypaisa.webhook_secret')) ? 'ok' : 'danger', 'Production webhooks must not accept unauthenticated callbacks.'),
                    $this->readinessCheck('Checkout URL', filled(config('easypaisa.checkout_url')) ? 'Configured' : 'Local pending page', filled(config('easypaisa.checkout_url')) ? 'ok' : 'warn', 'Blank checkout URL keeps the app in manual/local payment mode.'),
                    $this->readinessCheck('Callback window', config('easypaisa.callback_window').' seconds', (int) config('easypaisa.callback_window') > 0 ? 'ok' : 'warn', 'Fresh timestamps reduce replay risk.'),
                    $this->readinessCheck('Pending receipt reviews', (string) $pendingReviews, $pendingReviews > 0 ? 'warn' : 'ok', 'Manual receipts waiting for admin decision delay access.'),
                ],
            ],
            [
                'title' => 'Academy & Operations',
                'hint' => 'Moodle access and notifications need live services.',
                'icon' => 'academy',
                'checks' => [
                    $this->readinessCheck('Moodle base URL', config('moodle.base_url') ?: 'Missing', filled(config('moodle.base_url')) ? 'ok' : 'danger', 'Student access buttons and diagnostics use this URL.'),
                    $this->readinessCheck('Moodle REST URL', config('moodle.rest_url') ?: 'Missing', filled(config('moodle.rest_url')) ? 'ok' : 'danger', 'Course sync and enrolment jobs call this endpoint.'),
                    $this->readinessCheck('Moodle token', filled(config('moodle.token')) ? 'Configured' : 'Missing', filled(config('moodle.token')) ? 'ok' : 'danger', 'MOODLE_WS_TOKEN is required for sync and fulfilment.'),
                    $this->readinessCheck('Mail driver', config('mail.default'), in_array(config('mail.default'), ['log', 'array'], true) ? 'warn' : 'ok', 'Use a real mail transport for production notifications.'),
                    $this->readinessCheck('Failed enrolments', (string) $failedEnrollments, $failedEnrollments > 0 ? 'danger' : 'ok', 'Failed Moodle fulfilments need retry or configuration fixes.'),
                    $this->readinessCheck('Scheduler commands', 'Registered', 'warn', 'Run php artisan schedule:work or configure a server cron/Task Scheduler entry.'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readinessCheck(string $label, mixed $value, string $status, string $hint): array
    {
        return [
            'label' => $label,
            'value' => (string) $value,
            'status' => $status,
            'hint' => $hint,
        ];
    }
}
