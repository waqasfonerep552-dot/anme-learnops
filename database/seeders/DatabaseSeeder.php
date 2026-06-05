<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Starter roles/permissions business dashboard ke access control ke liye.
        $permissions = [
            'manage users',
            'manage courses',
            'manage orders',
            'view reports',
            'manage settings',
            'sync moodle',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate('Super Admin');
        $admin = Role::findOrCreate('Admin');
        $instructor = Role::findOrCreate('Instructor');
        $student = Role::findOrCreate('Student');

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions(['manage users', 'manage courses', 'manage orders', 'view reports', 'sync moodle']);
        $instructor->syncPermissions(['manage courses', 'view reports']);
        $student->syncPermissions([]);

        $adminUser = User::firstOrCreate([
            // Default admin .env se override ho sakta hai.
            'email' => env('LEARNOPS_ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'name' => 'ANME Super Admin',
            'phone' => '03000000000',
            'password' => Hash::make(env('LEARNOPS_ADMIN_PASSWORD', 'Password123!')),
            'status' => 'active',
        ]);

        if (!$adminUser->hasRole('Super Admin')) {
            $adminUser->assignRole($superAdmin);
        }

        $categories = collect([
            // Demo categories Moodle IDs ke sath mapped hain; real data sync command se update ho sakta hai.
            ['moodle_category_id' => 1, 'name' => 'Digital Skills', 'slug' => 'digital-skills'],
            ['moodle_category_id' => 2, 'name' => 'Business Growth', 'slug' => 'business-growth'],
            ['moodle_category_id' => 3, 'name' => 'Web Development', 'slug' => 'web-development'],
        ])->mapWithKeys(fn (array $category) => [
            $category['slug'] => Category::updateOrCreate(['slug' => $category['slug']], $category + ['status' => 'active']),
        ]);

        $courses = [
            // Starter catalog data sirf initial demo ke liye hai; Moodle sync live courses attach karta hai.
            [
                'category' => 'digital-skills',
                'moodle_course_id' => 2,
                'title' => 'Complete Digital Marketing Masterclass',
                'short_description' => 'Learn SEO, social media marketing, ad campaigns and analytics for a real training business.',
                'price' => 12500,
                'level' => 'Beginner to Intermediate',
                'duration' => '8 weeks',
                'instructor_name' => 'ANME Training Team',
                'is_featured' => true,
                'curriculum' => ['Market research', 'SEO foundations', 'Meta ads', 'Google analytics', 'Final campaign project'],
                'requirements' => ['Basic computer skills', 'Active email address'],
                'outcomes' => ['Launch a complete campaign', 'Read analytics reports', 'Prepare client-ready marketing plans'],
            ],
            [
                'category' => 'web-development',
                'moodle_course_id' => 3,
                'title' => 'Business Application Bootcamp',
                'short_description' => 'Build secure dashboards, orders, roles, payments and academy API integrations.',
                'price' => 18000,
                'level' => 'Intermediate',
                'duration' => '10 weeks',
                'instructor_name' => 'Senior Business Systems Mentor',
                'is_featured' => true,
                'curriculum' => ['Business app foundations', 'Secure auth', 'Role permissions', 'Payments', 'Queues and reports'],
                'requirements' => ['PHP basics', 'Local development setup'],
                'outcomes' => ['Build production-style modules', 'Connect academy APIs', 'Deploy a business dashboard'],
            ],
            [
                'category' => 'business-growth',
                'moodle_course_id' => 4,
                'title' => 'Training Business Operations',
                'short_description' => 'Design course sales, student onboarding, reporting and support workflows.',
                'price' => 9500,
                'level' => 'Beginner',
                'duration' => '4 weeks',
                'instructor_name' => 'Business Operations Coach',
                'is_featured' => true,
                'curriculum' => ['Offer design', 'Sales process', 'Student support', 'Reporting', 'Retention'],
                'requirements' => ['Interest in online training business'],
                'outcomes' => ['Create a launch plan', 'Track revenue and enrollments', 'Improve student experience'],
            ],
        ];

        foreach ($courses as $course) {
            // Course slug stable rakha gaya hai taake public links break na hon.
            Course::updateOrCreate([
                'slug' => Str::slug($course['title']),
            ], [
                'category_id' => $categories[$course['category']]->id,
                'moodle_course_id' => $course['moodle_course_id'],
                'title' => $course['title'],
                'short_description' => $course['short_description'],
                'price' => $course['price'],
                'currency' => 'PKR',
                'level' => $course['level'],
                'duration' => $course['duration'],
                'instructor_name' => $course['instructor_name'],
                'curriculum' => $course['curriculum'],
                'requirements' => $course['requirements'],
                'outcomes' => $course['outcomes'],
                'is_featured' => $course['is_featured'],
                'status' => 'draft',
                'is_admin_approved' => false,
                'approved_at' => null,
                'approved_by' => null,
            ]);
        }

        PlatformSetting::setValue('business_name', 'ANME LearnOps');
        // Platform settings admin panel se editable hain.
        PlatformSetting::setValue('business_tagline', 'Professional training, payments and ANME Academy access in one place.');
        PlatformSetting::setValue('academy_name', 'ANME Academy');
        PlatformSetting::setValue('academy_url', '');
        PlatformSetting::setValue('academy_access_label', 'Open ANME Academy');
        PlatformSetting::setValue('support_email', 'support@example.com');
        PlatformSetting::setValue('support_phone', '03000000000');
        PlatformSetting::setValue('support_whatsapp', '03000000000');
        PlatformSetting::setValue('default_currency', 'PKR');
        PlatformSetting::setValue('payment_gateway_label', 'Easypaisa');
        PlatformSetting::setValue('checkout_button_label', 'Continue to payment');
        PlatformSetting::setValue('easypaisa_manual_enabled', '1');
        PlatformSetting::setValue('easypaisa_account_title', '');
        PlatformSetting::setValue('easypaisa_account_number', '');
        PlatformSetting::setValue('easypaisa_till_id', '');
        PlatformSetting::setValue('easypaisa_qr_image_url', '');
        PlatformSetting::setValue('easypaisa_qr_image_path', '');
        PlatformSetting::setValue('easypaisa_payment_note', 'Scan the QR code or transfer to the Easypaisa account, then upload your receipt for admin verification.');
        PlatformSetting::setValue('payment_instructions', 'After Easypaisa payment is confirmed, your ANME Academy course access will be activated automatically.');
        PlatformSetting::setValue('payment_pending_message', 'Your payment is pending. Once payment is confirmed, ANME Academy access will be processed automatically.');
        PlatformSetting::setValue('payment_success_message', 'Payment confirmed. ANME Academy access is being prepared in the background.');
        PlatformSetting::setValue('fulfilment_retry_instructions', 'If academy access fails after a paid order, use retry fulfilment from the admin orders screen.');
        PlatformSetting::setValue('student_notifications_enabled', '1');
        PlatformSetting::setValue('moodle_purchase_email_enabled', '1');
        PlatformSetting::setValue('notification_panel_title', 'Latest updates');
        PlatformSetting::setValue('purchase_email_notice', 'A confirmation email is sent after paid enrolment is activated.');
        PlatformSetting::setValue('footer_note', 'Production-ready foundation for training businesses.');
        PlatformSetting::setValue('public_notice', '');
    }
}
