<?php

namespace Tests\Feature;

use App\Jobs\FulfillPaidOrder;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\NotificationCampaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\AcademyFulfillmentService;
use App\Services\MoodleService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LearnOpsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_student_order_and_pending_easypaisa_payment(): void
    {
        $course = $this->course();

        $response = $this->post(route('checkout.store', $course), [
            'name' => 'Waqas Student',
            'email' => 'waqas.student@example.com',
            'phone' => '03001234567',
            'password' => 'StrongPass123!',
        ]);

        $user = User::where('email', 'waqas.student@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('Student'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'gateway' => 'easypaisa', 'status' => 'pending']);

        $response->assertRedirect(route('payments.pending', Payment::first()));
    }

    public function test_student_submits_easypaisa_receipt_and_admin_approves_manual_payment(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->create(['email' => 'manual-approve-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course();

        $this->post(route('checkout.store', $course), [
            'name' => 'Manual Payer',
            'email' => 'manual.payer@example.com',
            'phone' => '03001234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $payment = Payment::with('order')->firstOrFail();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => 'order.created',
            'title' => 'New order started',
        ]);

        $this->post(route('payments.manual-proof.store', $payment), [
            'transaction_id' => 'EP-MANUAL-123',
            'sender_phone' => '03001234567',
            'paid_amount' => $payment->amount,
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 900, 1200),
        ])->assertRedirect(route('orders.show', $payment->order));

        $payment->refresh();

        $this->assertSame('pending_verification', $payment->status);
        $this->assertNull($payment->transaction_id);
        $this->assertSame('pending_verification', $payment->order->refresh()->status);
        $this->assertSame('local', data_get($payment->gateway_payload, 'manual_proof.receipt_disk'));
        Storage::disk('local')->assertExists(data_get($payment->gateway_payload, 'manual_proof.receipt_path'));
        Storage::disk('public')->assertMissing(data_get($payment->gateway_payload, 'manual_proof.receipt_path'));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => 'payment.receipt_submitted',
            'title' => 'Easypaisa receipt needs review',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $payment->user_id,
            'type' => 'payment.receipt_received',
            'title' => 'Receipt submitted for review',
        ]);

        $this->actingAs($admin)
            ->get(route('orders.show', $payment->order))
            ->assertOk()
            ->assertSee('Receipt verification workspace')
            ->assertSee('Verification checklist')
            ->assertSee('EP-MANUAL-123')
            ->assertSee('Approve Easypaisa Receipt')
            ->assertSee('Reject Receipt');

        $this->actingAs($payment->user)
            ->get(route('orders.receipt', $payment->order))
            ->assertOk();

        $otherStudent = User::factory()->create(['email' => 'receipt-blocked@example.com']);
        $this->actingAs($otherStudent)
            ->get(route('orders.receipt', $payment->order))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.orders.approve-manual-payment', $payment->order))
            ->assertRedirect();

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('EP-MANUAL-123', $payment->transaction_id);
        $this->assertSame('paid', $payment->order->refresh()->status);
        $this->assertSame('Manual verified', $payment->sourceLabel());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $payment->user_id,
            'type' => 'payment.approved',
            'title' => 'Payment approved',
        ]);
        Queue::assertPushed(FulfillPaidOrder::class);
    }

    public function test_admin_rejects_manual_payment_and_student_gets_notification(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create(['email' => 'manual-reject-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course();

        $this->post(route('checkout.store', $course), [
            'name' => 'Rejected Payer',
            'email' => 'rejected.payer@example.com',
            'phone' => '03001234568',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $payment = Payment::with('order')->firstOrFail();

        $this->post(route('payments.manual-proof.store', $payment), [
            'transaction_id' => 'EP-WRONG-123',
            'sender_phone' => '03001234568',
            'paid_amount' => $payment->amount,
            'receipt' => UploadedFile::fake()->image('wrong-receipt.jpg', 900, 1200),
        ])->assertRedirect(route('orders.show', $payment->order));

        $this->actingAs($admin)
            ->post(route('admin.orders.reject-manual-payment', $payment->order), [
                'admin_note' => 'Receipt amount does not match.',
            ])
            ->assertRedirect();

        $payment->refresh();

        $this->assertSame('rejected', $payment->status);
        $this->assertSame('pending', $payment->order->refresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $payment->user_id,
            'type' => 'payment.rejected',
            'title' => 'Payment receipt rejected',
        ]);
    }

    public function test_pending_payment_page_displays_manual_easypaisa_details(): void
    {
        $course = $this->course(['price' => 2]);
        $user = User::factory()->create([
            'name' => 'Payment Student',
            'email' => 'payment.student@example.com',
            'phone' => '03001234567',
        ]);

        PlatformSetting::setValue('easypaisa_manual_enabled', '1');
        PlatformSetting::setValue('easypaisa_account_title', 'Waqas H');
        PlatformSetting::setValue('easypaisa_account_number', '');
        PlatformSetting::setValue('easypaisa_till_id', '993148540');
        PlatformSetting::setValue('easypaisa_qr_image_path', 'easypaisa-qr/waqas-h-raast-qr.jpeg');
        PlatformSetting::setValue('easypaisa_qr_image_url', '');

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-PENDING-VIEW',
            'amount' => 2,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $order->items()->create([
            'course_id' => $course->id,
            'price' => 2,
            'currency' => 'PKR',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => 2,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-PENDING-VIEW',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->actingAs($user)
            ->get(route('payments.pending', $payment))
            ->assertOk()
            ->assertSee('Confirm order details')
            ->assertSee('Pay exact amount by Easypaisa/Raast')
            ->assertSee('Upload payment receipt')
            ->assertSee('Waqas H')
            ->assertSee('993148540')
            ->assertSee('PKR 2')
            ->assertSee('/storage/easypaisa-qr/waqas-h-raast-qr.jpeg', false);
    }

    public function test_manual_receipt_requires_exact_amount(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $course = $this->course(['price' => 2500]);

        $this->post(route('checkout.store', $course), [
            'name' => 'Wrong Amount Payer',
            'email' => 'wrong.amount@example.com',
            'phone' => '03001234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $payment = Payment::firstOrFail();

        $this->post(route('payments.manual-proof.store', $payment), [
            'transaction_id' => 'EP-AMOUNT-123',
            'sender_phone' => '03001234567',
            'paid_amount' => 2000,
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 900, 1200),
        ])->assertSessionHasErrors('paid_amount');

        $this->assertSame('pending', $payment->refresh()->status);
    }

    public function test_manual_receipt_transaction_id_cannot_be_reused(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $course = $this->course(['price' => 1500]);

        $this->post(route('checkout.store', $course), [
            'name' => 'First Payer',
            'email' => 'first.payer@example.com',
            'phone' => '03001234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $firstPayment = Payment::firstOrFail();

        $this->post(route('payments.manual-proof.store', $firstPayment), [
            'transaction_id' => 'ep-duplicate-123',
            'sender_phone' => '03001234567',
            'paid_amount' => 1500,
            'receipt' => UploadedFile::fake()->image('first-receipt.jpg', 900, 1200),
        ])->assertRedirect();

        auth()->logout();

        $this->post(route('checkout.store', $course), [
            'name' => 'Second Payer',
            'email' => 'second.payer@example.com',
            'phone' => '03007654321',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $secondPayment = Payment::latest('id')->firstOrFail();

        $this->post(route('payments.manual-proof.store', $secondPayment), [
            'transaction_id' => 'EP-DUPLICATE-123',
            'sender_phone' => '03007654321',
            'paid_amount' => 1500,
            'receipt' => UploadedFile::fake()->image('second-receipt.jpg', 900, 1200),
        ])->assertSessionHasErrors('transaction_id');

        $this->assertSame('pending', $secondPayment->refresh()->status);
    }

    public function test_unapproved_course_is_hidden_from_public_catalog_and_checkout(): void
    {
        $course = $this->course(['is_admin_approved' => false]);

        $this->get(route('courses.index'))->assertDontSee($course->title);
        $this->get(route('courses.show', $course))->assertNotFound();
        $this->get(route('checkout.show', $course))->assertNotFound();
    }

    public function test_admin_can_quick_approve_course_for_public_catalog(): void
    {
        $admin = User::factory()->create(['email' => 'quick-course-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course([
            'is_admin_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $this->get(route('courses.index'))->assertDontSee($course->title);

        $this->actingAs($admin)
            ->post(route('admin.courses.approve-public', $course))
            ->assertRedirect();

        $course->refresh();

        $this->assertTrue($course->is_admin_approved);
        $this->assertNotNull($course->approved_at);
        $this->assertSame($admin->id, $course->approved_by);

        $this->get(route('courses.index'))->assertSee($course->title);
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $student = User::factory()->create();
        Role::findOrCreate('Student');
        $student->assignRole('Student');

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_open_management_pages(): void
    {
        $admin = User::factory()->create(['email' => 'owner@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');
        $this->course();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.courses.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reviews.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.activity.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.production-readiness'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.moodle-diagnostics'))->assertOk();
    }

    public function test_existing_email_requires_correct_password_at_checkout(): void
    {
        $course = $this->course();
        User::create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $this->post(route('checkout.store', $course), [
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'phone' => '03001234567',
            'password' => 'WrongPass123!',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_logged_in_student_can_checkout_without_password(): void
    {
        $course = $this->course();
        $student = User::factory()->create([
            'name' => 'Logged Student',
            'email' => 'logged.student@example.com',
        ]);

        $this->actingAs($student)->post(route('checkout.store', $course), [
            'name' => 'Logged Student',
            'email' => 'logged.student@example.com',
            'phone' => '',
            'password' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_setting_can_require_phone_at_checkout(): void
    {
        PlatformSetting::setValue('student_phone_required', '1');

        $course = $this->course();

        $this->post(route('checkout.store', $course), [
            'name' => 'Phone Required Student',
            'email' => 'phone.required@example.com',
            'phone' => '',
            'password' => 'StrongPass123!',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('orders', 0);

        $this->post(route('checkout.store', $course), [
            'name' => 'Phone Required Student',
            'email' => 'phone.required@example.com',
            'phone' => '03001234567',
            'password' => 'StrongPass123!',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'phone.required@example.com',
            'phone' => '03001234567',
        ]);
    }

    public function test_admin_can_save_optional_course_fields_blank(): void
    {
        $admin = User::factory()->create(['email' => 'course-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');
        $course = $this->course();

        $this->actingAs($admin)->patch(route('admin.courses.update', $course), [
            'title' => '',
            'short_description' => '',
            'category_id' => '',
            'status' => '',
            'price' => '',
            'currency' => '',
            'level' => '',
            'duration' => '',
            'access_duration_days' => '',
            'instructor_name' => '',
            'is_featured' => '0',
            'is_admin_approved' => '0',
        ])->assertRedirect(route('admin.courses.index'));

        $course->refresh();

        $this->assertSame('Business LMS Course', $course->title);
        $this->assertSame('0.00', $course->price);
        $this->assertSame('PKR', $course->currency);
        $this->assertSame('draft', $course->status);
        $this->assertSame(0, $course->access_duration_days);
        $this->assertFalse($course->is_admin_approved);
    }

    public function test_admin_can_upload_optional_course_thumbnail(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'thumbnail-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');
        $course = $this->course();

        $this->actingAs($admin)->patch(route('admin.courses.update', $course), [
            'title' => $course->title,
            'short_description' => $course->short_description,
            'category_id' => $course->category_id,
            'status' => $course->status,
            'price' => $course->price,
            'currency' => $course->currency,
            'level' => '',
            'duration' => '',
            'instructor_name' => '',
            'is_featured' => '0',
            'is_admin_approved' => '1',
            'thumbnail' => UploadedFile::fake()->image('course-cover.jpg', 1200, 675),
        ])->assertRedirect(route('admin.courses.index'));

        $course->refresh();

        $this->assertNotNull($course->thumbnail);
        Storage::disk('public')->assertExists($course->thumbnail);
    }

    public function test_student_needs_paid_or_active_access_to_review_course(): void
    {
        $course = $this->course();
        $student = User::factory()->create(['email' => 'review-blocked@example.com']);
        Role::findOrCreate('Student');
        $student->assignRole('Student');

        $this->actingAs($student)->post(route('courses.reviews.store', $course), [
            'rating' => 5,
            'title' => 'Nice course',
            'body' => 'This course was practical and useful for my learning.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('course_reviews', [
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_paid_student_review_requires_admin_approval_before_public_display(): void
    {
        $course = $this->course();
        $student = User::factory()->create(['name' => 'Review Student', 'email' => 'review.student@example.com']);
        Role::findOrCreate('Student');
        $student->assignRole('Student');

        $order = Order::create([
            'user_id' => $student->id,
            'order_no' => 'ORD-REVIEW-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'course_id' => $course->id,
            'price' => $course->price,
            'currency' => 'PKR',
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)->post(route('courses.reviews.store', $course), [
            'rating' => 5,
            'title' => 'Excellent training',
            'body' => 'This course helped me understand the full business workflow clearly.',
        ])->assertRedirect();

        $review = CourseReview::first();

        $this->assertSame('pending', $review->status);
        $this->app['auth']->guard()->logout();
        $this->get(route('courses.show', $course))->assertDontSee('Excellent training');

        $admin = User::factory()->create(['email' => 'review-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->patch(route('admin.reviews.update', $review), [
            'status' => 'approved',
            'is_featured' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('course_reviews', [
            'id' => $review->id,
            'status' => 'approved',
            'is_featured' => true,
        ]);

        $this->get(route('courses.show', $course))
            ->assertSee('Excellent training')
            ->assertSee('This course helped me understand the full business workflow clearly.');
    }

    public function test_registration_can_use_email_prefix_when_name_is_blank(): void
    {
        $this->post(route('register'), [
            'name' => '',
            'email' => 'blank.name.student@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'blank.name.student@example.com',
            'name' => 'Blank Name Student',
        ]);
    }

    public function test_admin_can_create_notification_rule_with_blank_optional_copy(): void
    {
        $admin = User::factory()->create(['email' => 'notify-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->post(route('admin.notifications.store'), [
            'title' => '',
            'message' => '',
            'type' => '',
            'audience' => '',
            'trigger_event' => '',
        ])->assertRedirect();

        $campaign = NotificationCampaign::where('created_by', $admin->id)->latest()->first();

        $this->assertSame('New academy update', $campaign->title);
        $this->assertSame('students', $campaign->audience);
        $this->assertSame('login', $campaign->trigger_event);
    }

    public function test_admin_can_update_business_settings(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['email' => 'settings@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'business_name' => 'ANME Pro Academy',
            'business_tagline' => 'Premium learning operations',
            'support_email' => 'help@example.com',
            'support_phone' => '03001234567',
            'support_whatsapp' => '03007654321',
            'default_currency' => 'PKR',
            'student_phone_required' => '1',
            'payment_gateway_label' => 'Easypaisa',
            'checkout_button_label' => 'Continue to payment',
            'easypaisa_manual_enabled' => '1',
            'easypaisa_account_title' => 'ANME Academy',
            'easypaisa_account_number' => '03001234567',
            'easypaisa_till_id' => 'TILL-123',
            'easypaisa_qr_image_upload' => UploadedFile::fake()->image('easypaisa-qr.png', 600, 600),
            'easypaisa_qr_image_url' => 'images/payments/easypaisa-qr.png',
            'easypaisa_payment_note' => 'Send payment to Easypaisa and upload receipt.',
            'payment_instructions' => 'Pay with Easypaisa and wait for confirmation.',
            'payment_pending_message' => 'Payment is pending.',
            'payment_success_message' => 'Payment is confirmed.',
            'fulfilment_retry_instructions' => 'Retry Moodle fulfilment if access fails.',
            'student_notifications_enabled' => '1',
            'moodle_purchase_email_enabled' => '1',
            'notification_panel_title' => 'Student updates',
            'purchase_email_notice' => 'Moodle sends a confirmation email after access is ready.',
            'auth_visual_enabled' => '1',
            'auth_visual_mode' => 'background',
            'auth_image_upload' => UploadedFile::fake()->image('auth-background.jpg', 1600, 900),
            'auth_image_url' => 'images/auth/login.jpg',
            'auth_image_position' => 'right',
            'auth_panel_title' => 'Welcome learners',
            'auth_panel_subtitle' => 'Sign in to continue your academy journey.',
            'footer_note' => 'A real training business platform.',
            'public_notice' => 'Admissions are open this week.',
        ])->assertRedirect();

        $this->assertSame('ANME Pro Academy', PlatformSetting::getValue('business_name'));
        $this->assertSame('Continue to payment', PlatformSetting::getValue('checkout_button_label'));
        $this->assertTrue(PlatformSetting::boolean('easypaisa_manual_enabled'));
        $this->assertSame('ANME Academy', PlatformSetting::getValue('easypaisa_account_title'));
        $this->assertSame('03001234567', PlatformSetting::getValue('easypaisa_account_number'));
        Storage::disk('public')->assertExists(PlatformSetting::getValue('easypaisa_qr_image_path'));
        $this->assertTrue(PlatformSetting::boolean('student_phone_required'));
        $this->assertSame('Student updates', PlatformSetting::getValue('notification_panel_title'));
        $this->assertTrue(PlatformSetting::boolean('moodle_purchase_email_enabled'));
        $this->assertTrue(PlatformSetting::boolean('auth_visual_enabled'));
        $this->assertSame('background', PlatformSetting::getValue('auth_visual_mode'));
        $this->assertSame('images/auth/login.jpg', PlatformSetting::getValue('auth_image_url'));
        $this->assertSame('right', PlatformSetting::getValue('auth_image_position'));
        Storage::disk('public')->assertExists(PlatformSetting::getValue('auth_uploaded_image_path'));
        $this->assertSame('Admissions are open this week.', PlatformSetting::getValue('public_notice'));
    }

    public function test_admin_can_retry_paid_order_moodle_fulfillment(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['email' => 'retry-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-RETRY-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-RETRY-001',
            'status' => 'paid',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->actingAs($admin)
            ->post(route('admin.orders.retry-fulfillment', $order))
            ->assertRedirect();

        Queue::assertPushed(FulfillPaidOrder::class);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'order.fulfillment_retry_requested',
        ]);
    }

    public function test_admin_can_suspend_and_reactivate_paid_order_academy_access(): void
    {
        $admin = User::factory()->create(['email' => 'suspend-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course();
        $user = User::factory()->create([
            'email' => 'suspend.student@example.com',
            'moodle_user_id' => 88,
            'academy_username' => 'suspend_student',
            'academy_password_set_at' => now(),
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-SUSPEND-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-SUSPEND-001',
            'status' => 'paid',
            'transaction_id' => 'EP-SUSPEND-123',
        ]);
        $order->update(['payment_id' => $payment->id]);
        $order->items()->create([
            'course_id' => $course->id,
            'price' => $course->price,
            'currency' => 'PKR',
        ]);
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->mock(MoodleService::class, function ($mock): void {
            $mock->shouldReceive('suspendUser')
                ->twice()
                ->andReturn(['status' => true]);
        });

        $this->actingAs($admin)
            ->post(route('admin.orders.suspend-access', $order))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'course_access_suspended',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'enrollment.suspended',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Suspended Access');

        $this->actingAs($admin)
            ->post(route('admin.orders.reactivate-access', $order))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'course_access_reactivated',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'enrollment.reactivated',
        ]);
    }

    public function test_paid_order_waits_for_student_academy_login_setup(): void
    {
        $course = $this->course();
        $user = User::factory()->create(['email' => 'setup.waiting@example.com']);
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-SETUP-WAITING',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-SETUP-WAITING',
            'status' => 'paid',
        ]);
        $order->update(['payment_id' => $payment->id]);
        $order->items()->create([
            'course_id' => $course->id,
            'price' => $course->price,
            'currency' => 'PKR',
        ]);

        app(AcademyFulfillmentService::class)->fulfillQueuedOrder($order);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'setup_required',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'academy_setup_required',
        ]);
        $this->assertNotNull($user->refresh()->academy_setup_required_at);
        $this->assertNull($user->moodle_user_id);
    }

    public function test_student_can_set_academy_login_to_activate_paid_order(): void
    {
        $course = $this->course(['access_duration_days' => 90]);
        $user = User::factory()->create([
            'name' => 'Setup Student',
            'email' => 'setup.student@example.com',
        ]);
        $paidAt = now()->subMinute();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-SETUP-ACTIVE',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'paid',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-SETUP-ACTIVE',
            'status' => 'paid',
            'transaction_id' => 'EP-SETUP-123',
            'paid_at' => $paidAt,
        ]);
        $order->update(['payment_id' => $payment->id]);
        $order->items()->create([
            'course_id' => $course->id,
            'price' => $course->price,
            'currency' => 'PKR',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payment.manual_proof_submitted',
            'payload' => [
                'order_no' => $order->order_no,
                'reference_no' => $payment->reference_no,
            ],
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 ANME-Test-Browser',
        ]);

        $this->mock(MoodleService::class, function ($mock) use ($paidAt): void {
            $mock->shouldReceive('createUser')
                ->once()
                ->andReturn(['status' => true, 'userid' => 77, 'username' => 'setup_student', 'created' => true]);
            $mock->shouldReceive('updateUsers')
                ->once()
                ->andReturn([]);
            $mock->shouldReceive('enrolUser')
                ->once()
                ->with(\Mockery::on(function (array $payload) use ($paidAt): bool {
                    return $payload['customerip'] === '203.0.113.10'
                        && $payload['useragent'] === 'Mozilla/5.0 ANME-Test-Browser'
                        && $payload['timepurchased'] === $paidAt->timestamp
                        && isset($payload['timestart'], $payload['timeend'])
                        && ($payload['timeend'] - $payload['timestart']) === 90 * 24 * 60 * 60
                        && ! array_key_exists('rawpayload', $payload);
                }))
                ->andReturn(['status' => true, 'email_sent' => true]);
        });

        $this->actingAs($user)
            ->patch(route('academy-access.update'), [
                'academy_username' => 'setup_student',
                'academy_password' => 'AcademyPass123!',
                'academy_password_confirmation' => 'AcademyPass123!',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('setup_student', $user->academy_username);
        $this->assertSame(77, $user->moodle_user_id);
        $this->assertNotNull($user->academy_password_set_at);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertNotNull($enrollment->access_starts_at);
        $this->assertNotNull($enrollment->access_ends_at);
        $this->assertSame(90, (int) $enrollment->access_starts_at->diffInDays($enrollment->access_ends_at));
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'course_access_ready',
        ]);
    }

    public function test_admin_can_mark_pending_payment_paid_for_local_testing(): void
    {
        Queue::fake();
        config(['easypaisa.allow_local_paid_simulation' => true]);

        $admin = User::factory()->create(['email' => 'local-payment-admin@example.com']);
        Role::findOrCreate('Super Admin');
        $admin->assignRole('Super Admin');

        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-LOCAL-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-LOCAL-001',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->actingAs($admin)
            ->post(route('admin.orders.mark-paid-testing', $order))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'payment.admin_test_mark_paid',
        ]);

        $payment->refresh();
        $this->assertTrue($payment->isTestPayment());
        $this->assertSame('Admin test', $payment->sourceLabel());

        Queue::assertPushed(FulfillPaidOrder::class);
    }

    public function test_student_cannot_mark_pending_payment_paid_for_testing(): void
    {
        Queue::fake();

        $course = $this->course();
        $user = User::factory()->create();
        Role::findOrCreate('Student');
        $user->assignRole('Student');

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-STUDENT-SIM-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-STUDENT-SIM-001',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->actingAs($user)
            ->post(route('admin.orders.mark-paid-testing', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_id' => null,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
        Queue::assertNotPushed(FulfillPaidOrder::class);
    }

    public function test_payment_return_url_does_not_mark_payment_paid_from_query_string(): void
    {
        Queue::fake();

        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-RETURN-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-ORD-RETURN-001',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->actingAs($user)->get(route('payments.easypaisa.return', [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'transaction_id' => 'QUERY-STRING-FAKE',
        ]))->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_id' => null,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
        Queue::assertNotPushed(FulfillPaidOrder::class);
    }

    public function test_verified_easypaisa_webhook_marks_payment_as_gateway_paid_without_storing_secret(): void
    {
        Queue::fake();
        config(['easypaisa.webhook_secret' => 'expected-secret']);

        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-WEBHOOK-PAID-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-WEBHOOK-PAID-001',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);

        $this->postJson(route('api.payments.easypaisa.webhook'), [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'transaction_id' => 'EP-REAL-123',
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'timestamp' => now()->timestamp,
            'webhook_secret' => 'expected-secret',
        ])->assertOk();

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('EP-REAL-123', $payment->transaction_id);
        $this->assertFalse($payment->isTestPayment());
        $this->assertSame('Verified gateway', $payment->sourceLabel());
        $this->assertArrayNotHasKey('webhook_secret', $payment->gateway_payload['callback']);
        Queue::assertPushed(FulfillPaidOrder::class);
    }

    public function test_easypaisa_webhook_rejects_mismatched_amount(): void
    {
        Queue::fake();
        config(['easypaisa.webhook_secret' => 'expected-secret']);

        $course = $this->course();
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-WEBHOOK-AMOUNT-001',
            'status' => 'pending',
        ]);

        $this->postJson(route('api.payments.easypaisa.webhook'), [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'transaction_id' => 'EP-AMOUNT-BLOCKED',
            'amount' => (float) $payment->amount + 100,
            'currency' => $payment->currency,
            'timestamp' => now()->timestamp,
            'webhook_secret' => 'expected-secret',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_id' => null,
        ]);
        Queue::assertNotPushed(FulfillPaidOrder::class);
    }

    public function test_easypaisa_webhook_rejects_stale_timestamp(): void
    {
        Queue::fake();
        config([
            'easypaisa.webhook_secret' => 'expected-secret',
            'easypaisa.callback_window' => 300,
        ]);

        $course = $this->course();
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-WEBHOOK-STALE-001',
            'status' => 'pending',
        ]);

        $this->postJson(route('api.payments.easypaisa.webhook'), [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'transaction_id' => 'EP-STALE-BLOCKED',
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'timestamp' => now()->subMinutes(10)->timestamp,
            'webhook_secret' => 'expected-secret',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_id' => null,
        ]);
        Queue::assertNotPushed(FulfillPaidOrder::class);
    }

    public function test_easypaisa_webhook_requires_valid_signature_when_hash_key_is_configured(): void
    {
        Queue::fake();
        config([
            'easypaisa.webhook_secret' => 'expected-secret',
            'easypaisa.hash_key' => 'hash-secret',
        ]);

        $course = $this->course();
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-WEBHOOK-SIGNATURE-001',
            'amount' => $course->price,
            'currency' => 'PKR',
            'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-WEBHOOK-SIGNATURE-001',
            'status' => 'pending',
        ]);
        $order->update(['payment_id' => $payment->id]);
        $payload = [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'transaction_id' => 'EP-SIGNED-123',
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'timestamp' => now()->timestamp,
            'webhook_secret' => 'expected-secret',
        ];

        $this->postJson(route('api.payments.easypaisa.webhook'), $payload + [
            'signature' => 'wrong-signature',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'transaction_id' => null,
        ]);

        $signaturePayload = $payload;
        unset($signaturePayload['webhook_secret']);

        $signature = app(PaymentService::class)->signature($signaturePayload);

        $this->postJson(route('api.payments.easypaisa.webhook'), $payload + [
            'signature' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'transaction_id' => 'EP-SIGNED-123',
        ]);
        Queue::assertPushed(FulfillPaidOrder::class);
    }

    public function test_easypaisa_webhook_secret_blocks_invalid_requests(): void
    {
        config(['easypaisa.webhook_secret' => 'expected-secret']);

        $course = $this->course();
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $course->price,
            'currency' => 'PKR',
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-WEBHOOK-001',
            'status' => 'pending',
        ]);

        $this->postJson(route('api.payments.easypaisa.webhook'), [
            'reference_no' => $payment->reference_no,
            'status' => 'paid',
            'webhook_secret' => 'wrong-secret',
        ])->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    private function course(array $overrides = []): Course
    {
        $category = Category::create([
            'moodle_category_id' => 10,
            'name' => 'Business',
            'slug' => 'business',
            'status' => 'active',
        ]);

        return Course::create(array_merge([
            'category_id' => $category->id,
            'moodle_course_id' => 20,
            'title' => 'Business LMS Course',
            'slug' => 'business-lms-course',
            'short_description' => 'A production-style Moodle-connected course.',
            'price' => 5000,
            'currency' => 'PKR',
            'status' => 'published',
            'moodle_visible' => true,
            'is_admin_approved' => true,
            'approved_at' => now(),
        ], $overrides));
    }
}
