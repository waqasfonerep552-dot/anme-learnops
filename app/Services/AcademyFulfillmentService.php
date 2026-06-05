<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class AcademyFulfillmentService
{
    public function __construct(
        private MoodleService $moodle,
        private NotificationCampaignDispatcher $notifications,
        private SystemNotificationService $systemNotifications,
    ) {
    }

    public function fulfillQueuedOrder(Order $order): void
    {
        $order->load(['user', 'items.course', 'payment']);

        if ($order->status !== 'paid') {
            return;
        }

        if (! $order->user->hasAcademyPassword()) {
            $this->markSetupRequired($order);

            return;
        }

        if (! $order->user->moodle_user_id) {
            $this->markSetupRequired($order);

            return;
        }

        $this->enrolPaidOrder($order, $order->user->academyUsername());
    }

    public function fulfillWithPassword(Order $order, string $password): void
    {
        $order->load(['user', 'items.course', 'payment']);

        if ($order->status !== 'paid') {
            return;
        }

        $user = $order->user;
        $username = $user->academyUsername();
        $moodleUserId = $user->moodle_user_id;

        try {
            if (! $moodleUserId) {
                $created = $this->moodle->createUser($this->moodleCreateUserPayload($user, $username, $password));

                $moodleUserId = (int) ($created['userid'] ?? 0);

                if ($moodleUserId <= 0) {
                    throw new RuntimeException('Moodle user creation did not return a valid user id.');
                }

                if ($moodleUserId > 0 && ! $user->moodle_user_id) {
                    $user->update(['moodle_user_id' => $moodleUserId]);
                }
            }

            if ($moodleUserId > 0) {
                $this->moodle->updateUsers([array_merge(
                    $this->moodleProfileUpdatePayload($user, $moodleUserId),
                    ['password' => $password],
                )]);
            }
        } catch (Throwable $exception) {
            $this->recordFulfillmentFailure($order, $exception, 'moodle_user_setup');

            throw $exception;
        }

        $user->forceFill([
            'academy_username' => $username,
            'academy_password_set_at' => now(),
            'academy_setup_required_at' => null,
        ])->save();

        $this->enrolPaidOrder($order->refresh(), $username);
    }

    /**
     * @return array<string, string>
     */
    private function moodleCreateUserPayload(User $user, string $username, string $password): array
    {
        $payload = array_merge([
                'username' => $username,
                'password' => $password,
            ],
            $this->moodleProfileFields($user),
        );

        if (filled($user->phone)) {
            $payload['phone'] = $user->phone;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function moodleProfileUpdatePayload(User $user, int $moodleUserId): array
    {
        $payload = array_merge(['id' => $moodleUserId], $this->moodleProfileFields($user));

        if (filled($user->phone)) {
            $payload['phone1'] = $user->phone;
        }

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function moodleProfileFields(User $user): array
    {
        $parts = str($user->name)->squish();
        $fields = [
            'firstname' => $parts->before(' ')->toString() ?: $user->name,
            'lastname' => $parts->contains(' ') ? $parts->after(' ')->toString() : 'Student',
            'email' => $user->email,
            'country' => 'PK',
        ];

        return $fields;
    }

    public function markSetupRequired(Order $order): void
    {
        $order->load(['user', 'items.course']);
        $user = $order->user;

        foreach ($order->items as $item) {
            $enrollment = Enrollment::firstOrCreate([
                'user_id' => $user->id,
                'course_id' => $item->course_id,
            ], [
                'order_id' => $order->id,
                'status' => 'setup_required',
            ]);

            if ($enrollment->status !== 'active') {
                $enrollment->update([
                    'order_id' => $order->id,
                    'status' => 'setup_required',
                    'last_error' => null,
                ]);
            }
        }

        $user->forceFill([
            'academy_username' => $user->academyUsername(),
            'academy_setup_required_at' => $user->academy_setup_required_at ?: now(),
        ])->save();

        $alreadyNotified = $user->notifications()
            ->where('type', 'academy_setup_required')
            ->latest()
            ->take(20)
            ->get()
            ->contains(fn (UserNotification $notification): bool => data_get($notification->data, 'order_no') === $order->order_no);

        if (! $alreadyNotified) {
            UserNotification::create([
                'user_id' => $user->id,
                'type' => 'academy_setup_required',
                'title' => 'Set your ANME Academy login',
                'message' => 'Your payment is approved. Choose your Academy username and password to activate course access.',
                'data' => [
                    'order_no' => $order->order_no,
                    'setup_url' => route('academy-access.edit'),
                ],
            ]);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'academy.setup_required',
            'payload' => [
                'order_no' => $order->order_no,
                'academy_username' => $user->academyUsername(),
            ],
        ]);
    }

    private function enrolPaidOrder(Order $order, string $username): void
    {
        $order->load(['user', 'items.course', 'payment']);
        $user = $order->user;
        $emailResults = [];
        $sendMoodleEmail = PlatformSetting::boolean('moodle_purchase_email_enabled', true);
        $createStudentNotifications = PlatformSetting::boolean('student_notifications_enabled', true);
        $purchaseAuditData = $this->purchaseAuditData($order);

        foreach ($order->items as $item) {
            $course = $item->course;
            $enrollment = Enrollment::firstOrCreate([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ], [
                'order_id' => $order->id,
                'status' => 'pending',
            ]);
            [$accessStartsAt, $accessEndsAt] = $this->accessWindowFor($course, $enrollment);

            try {
                $enrolResponse = $this->moodle->enrolUser([
                    'courseid' => $course->moodle_course_id,
                    'username' => $username,
                    'amount' => $item->price,
                    'currency' => $item->currency,
                    'paymentstatus' => 'paid',
                    'sendemail' => $sendMoodleEmail ? 1 : 0,
                    'source' => 'business_platform_easypaisa',
                    'externalorderid' => $order->order_no,
                    'transactionid' => $order->payment?->transaction_id,
                    'timestart' => $accessStartsAt->timestamp,
                    'timeend' => $accessEndsAt?->timestamp ?? 0,
                ] + $purchaseAuditData);

                $emailResults[] = [
                    'course_id' => $course->id,
                    'moodle_course_id' => $course->moodle_course_id,
                    'email_sent' => (bool) ($enrolResponse['email_sent'] ?? false),
                    'email_message' => $enrolResponse['email_message'] ?? null,
                ];

                $enrollment->update([
                    'order_id' => $order->id,
                    'status' => 'active',
                    'enrolled_at' => $accessStartsAt,
                    'access_starts_at' => $accessStartsAt,
                    'access_ends_at' => $accessEndsAt,
                    'last_error' => null,
                ]);

                if ($createStudentNotifications) {
                    UserNotification::create([
                        'user_id' => $user->id,
                        'type' => 'course_access_ready',
                        'title' => 'Course access is ready',
                        'message' => "Your access to {$course->title} is active in ANME Academy.",
                        'data' => [
                            'order_no' => $order->order_no,
                            'course_id' => $course->id,
                            'moodle_course_id' => $course->moodle_course_id,
                            'academy_username' => $username,
                            'access_starts_at' => $accessStartsAt->toDateTimeString(),
                            'access_ends_at' => $accessEndsAt?->toDateTimeString(),
                            'moodle_email_sent' => (bool) ($enrolResponse['email_sent'] ?? false),
                        ],
                    ]);

                    $this->notifications->dispatchFor($user, 'purchase_success', [
                        'order_no' => $order->order_no,
                        'course_title' => $course->title,
                        'course_id' => $course->id,
                        'moodle_course_id' => $course->moodle_course_id,
                        'academy_username' => $username,
                        'access_ends_at' => $accessEndsAt?->toDateTimeString(),
                    ]);
                }
            } catch (Throwable $exception) {
                $enrollment->update([
                    'order_id' => $order->id,
                    'status' => 'failed',
                    'last_error' => $exception->getMessage(),
                ]);

                $this->recordFulfillmentFailure($order, $exception, 'moodle_enrolment', $course);

                throw $exception;
            }
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'order.fulfilled',
            'payload' => [
                'order_no' => $order->order_no,
                'academy_username' => $username,
                'moodle_purchase_email_enabled' => $sendMoodleEmail,
                'student_notifications_enabled' => $createStudentNotifications,
                'audit_data_sent' => array_keys($purchaseAuditData),
                'email_results' => $emailResults,
            ],
        ]);
    }

    /**
     * Course access window. Agar duration 0 hai to lifetime access hota hai
     * aur Moodle ko timeend=0 bhejte hain.
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon|null}
     */
    private function accessWindowFor(Course $course, Enrollment $enrollment): array
    {
        $accessStartsAt = $enrollment->access_starts_at ?? $enrollment->enrolled_at ?? now();
        $durationDays = max(0, (int) ($course->access_duration_days ?? 0));
        $accessEndsAt = $durationDays > 0 ? $accessStartsAt->copy()->addDays($durationDays) : null;

        return [$accessStartsAt, $accessEndsAt];
    }

    private function recordFulfillmentFailure(
        Order $order,
        Throwable $exception,
        string $stage,
        ?Course $course = null
    ): void {
        $order->loadMissing(['user', 'items.course', 'payment']);
        $user = $order->user;
        $message = $exception->getMessage();

        if ($user) {
            $items = $course
                ? $order->items->filter(fn ($item): bool => (int) $item->course_id === (int) $course->id)
                : $order->items;

            foreach ($items as $item) {
                Enrollment::updateOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $item->course_id,
                ], [
                    'order_id' => $order->id,
                    'status' => 'failed',
                    'last_error' => $message,
                ]);
            }
        }

        ActivityLog::create([
            'user_id' => $order->user_id,
            'action' => 'moodle.fulfillment_failed',
            'payload' => [
                'order_no' => $order->order_no,
                'stage' => $stage,
                'course_id' => $course?->id,
                'moodle_course_id' => $course?->moodle_course_id,
                'academy_username' => $user?->academyUsername(),
                'moodle_hmac_enabled' => (bool) config('moodle.hmac.enabled'),
                'moodle_function_group' => $stage === 'moodle_user_setup' ? 'create/update user' : 'enrol user',
                'error' => $message,
            ],
        ]);

        $this->notifyFulfillmentFailure($order, $message, $stage, $course);
    }

    private function notifyFulfillmentFailure(
        Order $order,
        string $message,
        string $stage,
        ?Course $course = null
    ): void {
        $isHmacIssue = str_contains(strtolower($message), 'hmac');
        $adminTitle = $isHmacIssue ? 'Moodle HMAC security check failed' : 'Moodle fulfilment failed';
        $courseLabel = $course ? " for {$course->title}" : '';
        $adminMessage = "Order {$order->order_no}{$courseLabel} could not be fulfilled. Stage: {$stage}. Error: {$message}";
        $cacheKey = sprintf(
            'fulfilment-alert:%s:%s:%s:%s',
            $order->id,
            $stage,
            $course?->id ?? 'all',
            md5($message)
        );

        if (Cache::add($cacheKey, true, now()->addMinutes(30))) {
            $this->systemNotifications->notifyAdmins(
                'moodle.fulfillment_failed',
                $adminTitle,
                $adminMessage,
                [
                    'order_no' => $order->order_no,
                    'url' => route('admin.orders.index', ['q' => $order->order_no]),
                    'stage' => $stage,
                    'course_id' => $course?->id,
                    'moodle_course_id' => $course?->moodle_course_id,
                    'hmac_related' => $isHmacIssue,
                ]
            );
        }

        if ($order->user && Cache::add('student-access-delay:'.$order->id.':'.($course?->id ?? 'all'), true, now()->addMinutes(30))) {
            $this->systemNotifications->notifyUser(
                $order->user,
                'course_access_delayed',
                'Academy access needs review',
                'Your payment is safe, but academy access needs admin review before activation. We will update you once it is ready.',
                [
                    'order_no' => $order->order_no,
                    'url' => route('orders.show', $order),
                    'course_id' => $course?->id,
                ]
            );
        }
    }

    /**
     * Safe Moodle purchase audit fields. Raw payload intentionally nahi bhejte,
     * kyunki usme sensitive form/payment data aa sakta hai.
     *
     * @return array<string, string|int>
     */
    private function purchaseAuditData(Order $order): array
    {
        $data = [];

        if ($order->payment?->paid_at) {
            $data['timepurchased'] = $order->payment->paid_at->timestamp;
        }

        $activity = ActivityLog::query()
            ->where('user_id', $order->user_id)
            ->whereIn('action', [
                'payment.manual_proof_submitted',
                'checkout.order_created',
                'payment.paid_webhook',
            ])
            ->latest()
            ->take(20)
            ->get()
            ->first(function (ActivityLog $activity) use ($order): bool {
                return data_get($activity->payload, 'order_no') === $order->order_no
                    || data_get($activity->payload, 'reference_no') === $order->payment?->reference_no;
            });

        if ($activity?->ip_address) {
            $data['customerip'] = (string) $activity->ip_address;
        }

        if ($activity?->user_agent) {
            $data['useragent'] = (string) $activity->user_agent;
        }

        return $data;
    }
}
