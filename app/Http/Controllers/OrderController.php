<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function show(Order $order, PaymentService $payments): View
    {
        // User apna order dekh sakta hai; admin kisi bhi order ko support ke liye dekh sakta hai.
        abort_unless(Auth::id() === $order->user_id || Auth::user()?->isAdmin(), 403);

        $order->load('items.course', 'payment', 'enrollments.course');

        return view('orders.show', [
            'order' => $order,
            'canMarkPaidForTesting' => Auth::user()?->isAdmin() && $payments->allowsLocalPaidSimulation(),
            'manualReviewChecks' => $this->manualReviewChecks($order),
            'platformSettings' => PlatformSetting::publicValues([
                'fulfilment_retry_instructions' => 'Use retry if academy access did not activate after paid payment.',
            ]),
        ]);
    }

    public function receipt(Order $order): BinaryFileResponse
    {
        // Receipts contain payment details, so files are served only through this authenticated route.
        abort_unless(Auth::id() === $order->user_id || Auth::user()?->isAdmin(), 403);

        $order->load('payment');

        $proof = data_get($order->payment?->gateway_payload, 'manual_proof', []);
        $receiptPath = (string) data_get($proof, 'receipt_path', '');
        $receiptDisk = (string) data_get($proof, 'receipt_disk', 'local');

        abort_if($receiptPath === '', 404);

        if (! in_array($receiptDisk, ['local', 'public'], true)) {
            $receiptDisk = 'local';
        }

        if (! Storage::disk($receiptDisk)->exists($receiptPath) && Storage::disk('public')->exists($receiptPath)) {
            $receiptDisk = 'public';
        }

        abort_unless(Storage::disk($receiptDisk)->exists($receiptPath), 404);

        return response()->file(Storage::disk($receiptDisk)->path($receiptPath), [
            'Cache-Control' => 'private, no-store, no-cache',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function manualReviewChecks(Order $order): array
    {
        $payment = $order->payment;
        $proof = data_get($payment?->gateway_payload, 'manual_proof', []);
        $transactionId = Payment::normaliseTransactionId((string) data_get($proof, 'transaction_id'));
        $submittedAmount = (float) data_get($proof, 'paid_amount', 0);
        $expectedAmount = (float) ($payment?->amount ?? $order->amount);
        $receiptPath = (string) data_get($proof, 'receipt_path', '');
        $receiptUrl = filled($receiptPath) ? route('orders.receipt', $order) : null;
        $receiptExtension = Str::lower(pathinfo($receiptPath, PATHINFO_EXTENSION));

        $checks = [
            'transaction_present' => $transactionId !== '',
            'amount_matches' => abs($submittedAmount - $expectedAmount) <= 0.01,
            'unique_reference' => $transactionId !== '' && ! Payment::transactionReferenceInUse($transactionId, $payment?->id),
            'receipt_attached' => filled($receiptPath),
        ];

        return [
            'transaction_id' => $transactionId,
            'submitted_amount' => $submittedAmount,
            'expected_amount' => $expectedAmount,
            'receipt_url' => $receiptUrl,
            'receipt_is_image' => in_array($receiptExtension, ['jpg', 'jpeg', 'png', 'webp'], true),
            'checks' => $checks,
            'ready_to_approve' => $payment?->status === 'pending_verification' && ! in_array(false, $checks, true),
        ];
    }
}
