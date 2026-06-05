<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Services\ActivityLogger;
use App\Services\EnrollmentService;
use App\Services\PaymentService;
use App\Services\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function pending(Payment $payment, PaymentService $payments): View
    {
        // Pending payment page private hai, taake kisi aur ka payment reference expose na ho.
        abort_unless(Auth::id() === $payment->user_id || Auth::user()?->isAdmin(), 403);

        return view('payments.pending', [
            'payment' => $payment->load('order.items.course', 'order.enrollments.course'),
            'canSimulatePaid' => Auth::user()?->isAdmin() && $payments->allowsLocalPaidSimulation(),
            'platformSettings' => PlatformSetting::publicValues([
                'easypaisa_manual_enabled' => '1',
                'easypaisa_account_title' => '',
                'easypaisa_account_number' => '',
                'easypaisa_till_id' => '',
                'easypaisa_qr_image_url' => '',
                'easypaisa_qr_image_path' => '',
                'easypaisa_payment_note' => 'Scan the QR code or transfer to the Easypaisa account, then upload your receipt for admin verification.',
                'payment_pending_message' => 'Your payment is pending. ANME Academy access will be processed after confirmation.',
            ]),
        ]);
    }

    public function submitManualProof(Request $request, Payment $payment, ActivityLogger $activity, SystemNotificationService $notifications): RedirectResponse
    {
        // Manual Easypaisa receipt sirf payment owner submit kar sakta hai; paid payment dobara submit nahi hoti.
        abort_unless(Auth::id() === $payment->user_id, 403);
        abort_unless(PlatformSetting::boolean('easypaisa_manual_enabled', true), 403);
        abort_if($payment->status === 'paid', 403);

        $data = $request->validate([
            'transaction_id' => ['required', 'string', 'max:100'],
            'sender_phone' => ['nullable', 'string', 'max:40'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $transactionId = Payment::normaliseTransactionId($data['transaction_id']);
        $expectedAmount = (float) $payment->amount;
        $submittedAmount = (float) $data['paid_amount'];

        if (abs($submittedAmount - $expectedAmount) > 0.01) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount must match exact order amount: '.$payment->currency.' '.number_format($expectedAmount),
            ]);
        }

        if (Payment::transactionReferenceInUse($transactionId, $payment->id)) {
            throw ValidationException::withMessages([
                'transaction_id' => 'This Easypaisa transaction ID is already attached to another payment.',
            ]);
        }

        $oldReceiptPath = data_get($payment->gateway_payload, 'manual_proof.receipt_path');
        $oldReceiptDisk = data_get($payment->gateway_payload, 'manual_proof.receipt_disk', 'local');
        $this->deleteReceipt($oldReceiptPath, $oldReceiptDisk);

        $receipt = $request->file('receipt');
        $receiptPath = $receipt->store('easypaisa-receipts', 'local');
        $payload = $payment->gateway_payload ?? [];
        $payload['manual_proof'] = [
            'status' => 'pending_review',
            'transaction_id' => $transactionId,
            'sender_phone' => trim((string) ($data['sender_phone'] ?? '')),
            'paid_amount' => $submittedAmount,
            'receipt_path' => $receiptPath,
            'receipt_disk' => 'local',
            'receipt_original_name' => $receipt->getClientOriginalName(),
            'receipt_mime_type' => $receipt->getClientMimeType(),
            'submitted_at' => now()->toISOString(),
        ];

        $payment->update([
            'status' => 'pending_verification',
            'transaction_id' => null,
            'gateway_payload' => $payload,
            'paid_at' => null,
        ]);

        $payment->order?->update(['status' => 'pending_verification']);

        $activity->log('payment.manual_proof_submitted', [
            'reference_no' => $payment->reference_no,
            'order_no' => $payment->order?->order_no,
            'transaction_id' => $transactionId,
            'paid_amount' => $submittedAmount,
        ], $payment->user, $request);

        $payment->load('user', 'order.items.course');
        $courseTitles = $payment->order?->items
            ->pluck('course.title')
            ->filter()
            ->join(', ') ?: 'course access';

        $notifications->notifyAdmins(
            'payment.receipt_submitted',
            'Easypaisa receipt needs review',
            "{$payment->user?->name} uploaded a payment receipt for {$payment->order?->order_no}. Please verify and approve/reject.",
            [
                'order_no' => $payment->order?->order_no,
                'payment_id' => $payment->id,
                'student_id' => $payment->user_id,
                'transaction_id' => $transactionId,
                'paid_amount' => $submittedAmount,
                'course_titles' => $courseTitles,
                'url' => $payment->order ? route('orders.show', $payment->order) : route('admin.orders.index', ['q' => $payment->reference_no]),
            ]
        );

        if ($payment->user) {
            $notifications->notifyUser(
                $payment->user,
                'payment.receipt_received',
                'Receipt submitted for review',
                "Your Easypaisa receipt for {$courseTitles} is submitted. Admin verification is pending.",
                [
                    'order_no' => $payment->order?->order_no,
                    'payment_id' => $payment->id,
                    'url' => $payment->order ? route('orders.show', $payment->order) : route('dashboard'),
                ]
            );
        }

        return redirect()
            ->route('orders.show', $payment->order)
            ->with('status', 'Easypaisa receipt submitted. Admin verification is pending.');
    }

    public function return(Request $request): RedirectResponse
    {
        // Gateway return URL sirf user ko order screen par wapas lata hai.
        // Payment ko paid mark karna sirf verified webhook ya admin-only testing action karega.
        $payment = Payment::where('reference_no', $request->query('reference_no'))->firstOrFail();

        return redirect()
            ->route('orders.show', $payment->order)
            ->with('status', 'Payment return received. Course access will activate only after verified Easypaisa confirmation.');
    }

    public function webhook(Request $request, PaymentService $payments, EnrollmentService $enrollments, ActivityLogger $activity): array
    {
        // Webhook secret invalid ho to payment status update allow nahi hota.
        abort_unless($payments->webhookSecretIsValid(
            $request->header('X-Webhook-Secret') ?? $request->input('webhook_secret')
        ), 403);

        $data = $request->validate([
            'reference_no' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'max:40'],
            'transaction_id' => [Rule::requiredIf($request->input('status') === 'paid'), 'nullable', 'string', 'max:100'],
            'amount' => [Rule::requiredIf($request->input('status') === 'paid'), 'nullable', 'numeric', 'min:0'],
            'currency' => [Rule::requiredIf($request->input('status') === 'paid'), 'nullable', 'string', 'size:3'],
            'timestamp' => [Rule::requiredIf($request->input('status') === 'paid'), 'nullable', 'integer'],
            'signature' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = Payment::where('reference_no', $data['reference_no'])->firstOrFail();

        if ($data['status'] === 'paid') {
            // Real gateway webhook paid bole to order fulfilment start hoti hai.
            $transactionId = Payment::normaliseTransactionId((string) $data['transaction_id']);
            $amount = (float) $data['amount'];
            $currency = strtoupper((string) $data['currency']);
            $expectedAmount = (float) $payment->amount;
            $wasAlreadyPaid = $payment->status === 'paid';

            if (abs($amount - $expectedAmount) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Webhook amount does not match the payment amount.',
                ]);
            }

            if ($currency !== strtoupper((string) $payment->currency)) {
                throw ValidationException::withMessages([
                    'currency' => 'Webhook currency does not match the payment currency.',
                ]);
            }

            if (! $payments->webhookTimestampIsFresh((int) $data['timestamp'])) {
                throw ValidationException::withMessages([
                    'timestamp' => 'Webhook timestamp is outside the allowed callback window.',
                ]);
            }

            if (Payment::transactionReferenceInUse($transactionId, $payment->id)) {
                throw ValidationException::withMessages([
                    'transaction_id' => 'This Easypaisa transaction ID is already attached to another payment.',
                ]);
            }

            if (! $payments->webhookSignatureIsValid($request->except('webhook_secret'), $request->input('signature'))) {
                throw ValidationException::withMessages([
                    'signature' => 'Webhook signature is invalid.',
                ]);
            }

            $payments->markPaid($payment, array_merge($request->except('webhook_secret', 'signature'), [
                'transaction_id' => $transactionId,
                'mode' => 'verified_webhook',
            ]));

            if (! $wasAlreadyPaid) {
                $enrollments->queueFulfillment($payment->order);
            }

            $activity->log('payment.paid_webhook', [
                'reference_no' => $payment->reference_no,
                'order_no' => $payment->order?->order_no,
                'gateway' => $payment->gateway,
                'mode' => 'webhook',
            ], $payment->user, $request);
        }

        return ['ok' => true];
    }

    private function deleteReceipt(mixed $path, mixed $disk): void
    {
        $receiptPath = (string) $path;
        $receiptDisk = in_array($disk, ['local', 'public'], true) ? (string) $disk : 'local';

        if ($receiptPath === '') {
            return;
        }

        if (Storage::disk($receiptDisk)->exists($receiptPath)) {
            Storage::disk($receiptDisk)->delete($receiptPath);

            return;
        }

        if ($receiptDisk !== 'public' && Storage::disk('public')->exists($receiptPath)) {
            Storage::disk('public')->delete($receiptPath);
        }
    }
}
