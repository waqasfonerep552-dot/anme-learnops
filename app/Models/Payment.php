<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Payment extends Model
{
    // Payment Easypaisa transaction/reference aur callback payload store karta hai.
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'gateway',
        'transaction_id',
        'reference_no',
        'status',
        'gateway_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isTestPayment(): bool
    {
        // Admin ko clear signal milta hai ke payment real gateway se aayi ya local testing se.
        $transactionId = (string) $this->transaction_id;
        $callbackMode = (string) data_get($this->gateway_payload, 'callback.mode');
        $rolledBackTransactionId = (string) data_get($this->gateway_payload, 'rollback.old_transaction_id');

        return Str::startsWith($transactionId, ['LOCAL-', 'ADMIN-TEST-'])
            || Str::startsWith($rolledBackTransactionId, ['LOCAL-', 'ADMIN-TEST-'])
            || $callbackMode === 'admin_local_paid_simulation';
    }

    public function sourceLabel(): string
    {
        if ($this->status !== 'paid') {
            return match ($this->status) {
                'pending_verification' => 'Awaiting admin review',
                'rejected' => 'Proof rejected',
                default => $this->wasTestPaymentRolledBack() ? 'Reset test' : 'Awaiting payment',
            };
        }

        if ($this->isTestPayment()) {
            return Str::startsWith((string) $this->transaction_id, 'ADMIN-TEST-') ? 'Admin test' : 'Local test';
        }

        return match (data_get($this->gateway_payload, 'callback.mode')) {
            'verified_webhook' => 'Verified gateway',
            'admin_manual_easypaisa_verification' => 'Manual verified',
            default => 'Gateway paid',
        };
    }

    public function sourceVariant(): string
    {
        if ($this->status !== 'paid') {
            return match ($this->status) {
                'pending_verification' => 'blue',
                'rejected' => 'red',
                default => $this->wasTestPaymentRolledBack() ? 'slate' : 'orange',
            };
        }

        return $this->isTestPayment() ? 'dark' : 'green';
    }

    public function wasTestPaymentRolledBack(): bool
    {
        return filled(data_get($this->gateway_payload, 'rollback.old_transaction_id'));
    }

    public static function normaliseTransactionId(string $transactionId): string
    {
        return Str::upper(trim($transactionId));
    }

    public static function transactionReferenceInUse(string $transactionId, ?int $ignorePaymentId = null): bool
    {
        $transactionId = self::normaliseTransactionId($transactionId);

        if ($transactionId === '') {
            return false;
        }

        return self::query()
            ->when($ignorePaymentId, fn ($query) => $query->whereKeyNot($ignorePaymentId))
            ->whereIn('status', ['pending_verification', 'paid'])
            ->where(function ($query) use ($transactionId): void {
                $query->where('transaction_id', $transactionId)
                    ->orWhere('gateway_payload->manual_proof->transaction_id', $transactionId);
            })
            ->exists();
    }
}
