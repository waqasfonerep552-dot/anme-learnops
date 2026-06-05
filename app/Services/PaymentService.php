<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    public function createEasypaisaPayment(Order $order): Payment
    {
        // Yahan payment abhi sirf pending banti hai; Moodle enrolment payment paid hone ke baad hi chalegi.
        $payment = Payment::create([
            'user_id' => $order->user_id,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'gateway' => 'easypaisa',
            'reference_no' => 'EP-'.$order->order_no,
            'status' => 'pending',
            'gateway_payload' => [
                'mode' => config('easypaisa.mode'),
                'store_id' => config('easypaisa.store_id'),
                'return_url' => config('easypaisa.return_url'),
            ],
        ]);

        $order->update(['payment_id' => $payment->id]);

        return $payment;
    }

    public function checkoutUrl(Payment $payment): string
    {
        // Agar Easypaisa real checkout URL configured hai to user gateway par jayega, warna local demo pending page dikhega.
        if (filled(config('easypaisa.checkout_url'))) {
            return config('easypaisa.checkout_url').'?'.http_build_query([
                'storeId' => config('easypaisa.store_id'),
                'orderRefNum' => $payment->reference_no,
                'amount' => $payment->amount,
                'returnUrl' => config('easypaisa.return_url'),
                'signature' => $this->signature([
                    'reference_no' => $payment->reference_no,
                    'amount' => $payment->amount,
                ]),
            ]);
        }

        return route('payments.pending', $payment);
    }

    public function markPaid(Payment $payment, array $payload = []): Payment
    {
        if ($payment->status === 'paid') {
            return $payment->refresh();
        }

        $transactionId = $payload['transaction_id'] ?? $payload['transactionId'] ?? 'EP-'.Str::upper(Str::random(10));

        // Gateway callback/return ke baad payment aur order ko paid mark karte hain.
        $payment->update([
            'status' => 'paid',
            'transaction_id' => Payment::normaliseTransactionId((string) $transactionId),
            'gateway_payload' => array_merge($payment->gateway_payload ?? [], ['callback' => $payload]),
            'paid_at' => now(),
        ]);

        $payment->order?->update(['status' => 'paid']);

        return $payment->refresh();
    }

    public function allowsLocalPaidSimulation(): bool
    {
        // Local testing simulation sirf admin-only controls ke liye hai; production mein kabhi allow nahi.
        return app()->environment(['local', 'testing'])
            && blank(config('easypaisa.checkout_url'))
            && (bool) config('easypaisa.allow_local_paid_simulation');
    }

    public function webhookSecretIsValid(?string $providedSecret): bool
    {
        $secret = (string) config('easypaisa.webhook_secret');

        if (blank($secret) && app()->environment('production')) {
            return false;
        }

        if (blank($secret)) {
            return true;
        }

        return is_string($providedSecret) && hash_equals($secret, $providedSecret);
    }

    public function webhookTimestampIsFresh(int $timestamp): bool
    {
        $window = (int) config('easypaisa.callback_window', 300);

        if ($window <= 0) {
            return true;
        }

        return abs(now()->timestamp - $timestamp) <= $window;
    }

    public function webhookSignatureIsValid(array $payload, ?string $providedSignature): bool
    {
        if (blank(config('easypaisa.hash_key'))) {
            return true;
        }

        if (! is_string($providedSignature) || $providedSignature === '') {
            return false;
        }

        unset($payload['signature'], $payload['webhook_secret']);

        return hash_equals($this->signature($payload), $providedSignature);
    }

    public function signature(array $payload): string
    {
        // Payload sort karna zaroori hai taake har dafa same data se same HMAC signature bane.
        ksort($payload);

        return hash_hmac('sha256', http_build_query($payload), (string) config('easypaisa.hash_key'));
    }
}
