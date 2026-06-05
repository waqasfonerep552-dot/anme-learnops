<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\UserNotification;
use App\Services\ActivityLogger;
use App\Services\EnrollmentService;
use App\Services\MoodleService;
use App\Services\PaymentService;
use App\Services\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request, PaymentService $payments): View
    {
        // Admin order table payment, user aur course item ke sath searchable/filterable hai.
        $orders = Order::query()
            ->with(['user', 'payment', 'items.course', 'enrollments.course'])
            ->when($request->query('status'), function ($query, $status): void {
                $query->where(function ($query) use ($status): void {
                    $query->where('status', $status)
                        ->orWhereHas('payment', fn ($payment) => $payment->where('status', $status));
                });
            })
            ->when($request->query('q'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('order_no', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('payment', fn ($payment) => $payment
                            ->where('reference_no', 'like', '%'.$search.'%')
                            ->orWhere('transaction_id', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'canMarkPaidForTesting' => $payments->allowsLocalPaidSimulation(),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function markPaidForTesting(Order $order, PaymentService $payments, EnrollmentService $enrollments, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($payments->allowsLocalPaidSimulation(), 403);

        $order->load(['payment', 'user']);

        if (! $order->payment) {
            return back()->with('error', 'This order has no Easypaisa payment record to mark as paid.');
        }

        if ($order->status === 'paid' || $order->payment->status === 'paid') {
            return back()->with('status', 'This order is already marked paid.');
        }

        $payments->markPaid($order->payment, [
            'transaction_id' => 'ADMIN-TEST-'.now()->format('YmdHis'),
            'mode' => 'admin_local_paid_simulation',
            'admin_user_id' => auth()->id(),
        ]);

        $enrollments->queueFulfillment($order->refresh());

        $activity->log('payment.admin_test_mark_paid', [
            'order_no' => $order->order_no,
            'reference_no' => $order->payment->reference_no,
            'target_user_id' => $order->user_id,
        ]);

        return back()->with('status', 'Admin test payment marked paid and academy fulfilment has been queued.');
    }

    public function approveManualPayment(Order $order, PaymentService $payments, EnrollmentService $enrollments, ActivityLogger $activity, SystemNotificationService $notifications): RedirectResponse
    {
        $order->load(['payment', 'user', 'items.course']);

        if (! $order->payment) {
            return back()->with('error', 'This order has no Easypaisa payment record.');
        }

        if ($order->payment->status !== 'pending_verification') {
            return back()->with('error', 'Only payments waiting for manual verification can be approved.');
        }

        $proof = data_get($order->payment->gateway_payload, 'manual_proof', []);
        $transactionId = Payment::normaliseTransactionId((string) data_get($proof, 'transaction_id'));
        $submittedAmount = (float) data_get($proof, 'paid_amount', 0);
        $expectedAmount = (float) $order->payment->amount;

        if ($transactionId === '') {
            return back()->with('error', 'Cannot approve receipt without Easypaisa transaction ID.');
        }

        if (abs($submittedAmount - $expectedAmount) > 0.01) {
            return back()->with('error', 'Cannot approve receipt because submitted amount does not match order amount.');
        }

        if (Payment::transactionReferenceInUse($transactionId, $order->payment->id)) {
            return back()->with('error', 'This Easypaisa transaction ID is already used by another payment.');
        }

        $payments->markPaid($order->payment, [
            'transaction_id' => $transactionId,
            'mode' => 'admin_manual_easypaisa_verification',
            'admin_user_id' => auth()->id(),
            'manual_proof_status' => 'approved',
        ]);

        $enrollments->queueFulfillment($order->refresh());

        $activity->log('payment.manual_approved', [
            'order_no' => $order->order_no,
            'reference_no' => $order->payment->reference_no,
            'target_user_id' => $order->user_id,
            'transaction_id' => $transactionId,
        ]);

        if ($order->user) {
            $courseTitles = $order->items->pluck('course.title')->filter()->join(', ') ?: 'your course';

            $notifications->notifyUser(
                $order->user,
                'payment.approved',
                'Payment approved',
                "Your payment for {$courseTitles} has been approved. Academy access is now being prepared.",
                [
                    'order_no' => $order->order_no,
                    'transaction_id' => $transactionId,
                    'url' => route('orders.show', $order),
                ]
            );
        }

        return back()->with('status', 'Easypaisa receipt approved. Academy fulfilment has been queued.');
    }

    public function rejectManualPayment(Request $request, Order $order, ActivityLogger $activity, SystemNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:300'],
        ]);

        $order->load(['payment', 'user', 'items.course']);

        if (! $order->payment) {
            return back()->with('error', 'This order has no Easypaisa payment record.');
        }

        if (! in_array($order->payment->status, ['pending_verification', 'rejected'], true)) {
            return back()->with('error', 'Only manually submitted receipts can be rejected.');
        }

        $payload = $order->payment->gateway_payload ?? [];
        $payload['manual_review'] = [
            'status' => 'rejected',
            'admin_user_id' => auth()->id(),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')),
            'reviewed_at' => now()->toISOString(),
        ];

        $order->payment->update([
            'status' => 'rejected',
            'gateway_payload' => $payload,
            'paid_at' => null,
        ]);

        $order->update(['status' => 'pending']);

        $activity->log('payment.manual_rejected', [
            'order_no' => $order->order_no,
            'reference_no' => $order->payment->reference_no,
            'target_user_id' => $order->user_id,
            'admin_note' => $payload['manual_review']['admin_note'],
        ]);

        if ($order->user) {
            $courseTitles = $order->items->pluck('course.title')->filter()->join(', ') ?: 'your course';
            $note = $payload['manual_review']['admin_note'];
            $message = "Your Easypaisa receipt for {$courseTitles} was not approved. Please upload a correct receipt.";

            if ($note !== '') {
                $message .= " Admin note: {$note}";
            }

            $notifications->notifyUser(
                $order->user,
                'payment.rejected',
                'Payment receipt rejected',
                $message,
                [
                    'order_no' => $order->order_no,
                    'admin_note' => $note,
                    'url' => route('orders.show', $order),
                ]
            );
        }

        return back()->with('status', 'Easypaisa receipt rejected. Student can submit corrected proof again.');
    }

    public function retryFulfillment(Order $order, EnrollmentService $enrollments, ActivityLogger $activity): RedirectResponse
    {
        $order->load('payment');
        $isPaid = $order->status === 'paid' || $order->payment?->status === 'paid';

        if (! $isPaid) {
            return back()->with('error', 'Only paid orders can be retried for academy fulfilment.');
        }

        // Admin retry se same fulfilment job dobara queue hoti hai; duplicate enrolment unique key se avoid hoti hai.
        $enrollments->queueFulfillment($order);

        $activity->log('order.fulfillment_retry_requested', [
            'order_no' => $order->order_no,
            'target_user_id' => $order->user_id,
        ]);

        return back()->with('status', 'Academy fulfilment retry has been queued for '.$order->order_no.'.');
    }

    public function suspendAccess(Order $order, MoodleService $moodle, ActivityLogger $activity): RedirectResponse
    {
        return $this->toggleAccessSuspension($order, $moodle, $activity, true);
    }

    public function reactivateAccess(Order $order, MoodleService $moodle, ActivityLogger $activity): RedirectResponse
    {
        return $this->toggleAccessSuspension($order, $moodle, $activity, false);
    }

    private function toggleAccessSuspension(Order $order, MoodleService $moodle, ActivityLogger $activity, bool $suspend): RedirectResponse
    {
        $order->load(['user', 'payment', 'enrollments.course']);

        $isPaid = $order->status === 'paid' || $order->payment?->status === 'paid';
        if (! $isPaid) {
            return back()->with('error', 'Only paid orders can have academy access suspended or reactivated.');
        }

        if (! $order->user) {
            return back()->with('error', 'This order has no student account attached.');
        }

        $fromStatus = $suspend ? 'active' : 'suspended';
        $toStatus = $suspend ? 'suspended' : 'active';
        $actionLabel = $suspend ? 'suspended' : 'reactivated';

        $targetEnrollments = $order->enrollments
            ->filter(fn (Enrollment $enrollment): bool => $enrollment->status === $fromStatus && $enrollment->course?->moodle_course_id)
            ->values();

        if ($targetEnrollments->isEmpty()) {
            return back()->with('error', $suspend
                ? 'No active academy access found to suspend for this order.'
                : 'No suspended academy access found to reactivate for this order.');
        }

        $updated = [];
        $errors = [];

        foreach ($targetEnrollments as $enrollment) {
            try {
                $moodle->suspendUser([
                    'courseid' => (int) $enrollment->course->moodle_course_id,
                    'userid' => (int) ($order->user->moodle_user_id ?? 0),
                    'username' => $order->user->academyUsername(),
                    'suspended' => $suspend ? 1 : 0,
                ]);

                $enrollment->update([
                    'status' => $toStatus,
                    'last_error' => null,
                    'enrolled_at' => $toStatus === 'active' ? ($enrollment->enrolled_at ?: now()) : $enrollment->enrolled_at,
                ]);

                $updated[] = [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'moodle_course_id' => $enrollment->course->moodle_course_id,
                ];
            } catch (Throwable $exception) {
                $enrollment->update(['last_error' => $exception->getMessage()]);
                $errors[] = [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        if ($updated !== []) {
            UserNotification::create([
                'user_id' => $order->user_id,
                'type' => $suspend ? 'course_access_suspended' : 'course_access_reactivated',
                'title' => $suspend ? 'Academy access suspended' : 'Academy access reactivated',
                'message' => $suspend
                    ? 'Your course access has been temporarily suspended by admin. Please contact support for help.'
                    : 'Your course access has been reactivated. You can continue learning in ANME Academy.',
                'data' => [
                    'order_no' => $order->order_no,
                    'enrollments' => $updated,
                ],
            ]);
        }

        $activity->log($suspend ? 'enrollment.suspended' : 'enrollment.reactivated', [
            'order_no' => $order->order_no,
            'target_user_id' => $order->user_id,
            'academy_username' => $order->user->academyUsername(),
            'updated' => $updated,
            'errors' => $errors,
        ]);

        if ($updated === []) {
            return back()->with('error', 'Academy access could not be '.$actionLabel.'. Check fulfilment error details.');
        }

        return back()->with('status', count($updated).' academy access record(s) '.$actionLabel.' for '.$order->order_no.'.');
    }
}
