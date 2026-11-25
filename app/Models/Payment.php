<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use GeneratesSequentialNumber;

    public const METHODS = [
        'cash' => 'Cash',
        'card' => 'Credit / Debit Card',
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'check' => 'Check',
    ];

    protected $fillable = [
        'receipt_number',
        'membership_transaction_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference_note',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (blank($payment->receipt_number)) {
                $payment->receipt_number = static::generateReceiptNumber();
            }
        });
    }

    /** Format: OR-20261102-0001 */
    public static function generateReceiptNumber(): string
    {
        return static::nextSequentialNumber('receipt_number', 'OR-'.now()->format('Ymd').'-', 4);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'membership_transaction_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }
}
