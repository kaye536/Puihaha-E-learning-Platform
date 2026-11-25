<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTransaction extends Model
{
    use GeneratesSequentialNumber;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_APPROVED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    public const TYPE_APPLICATION = 'application';
    public const TYPE_RENEWAL = 'renewal';

    public const TYPES = [self::TYPE_APPLICATION, self::TYPE_RENEWAL];

    protected $fillable = [
        'reference_number',
        'member_id',
        'membership_level_id',
        'type',
        'amount',
        'status',
        'transaction_date',
        'period_start',
        'period_end',
        'remarks',
        'latitude',
        'longitude',
        'verification_sent_at',
        'processed_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'approved_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MembershipTransaction $transaction) {
            if (blank($transaction->reference_number)) {
                $transaction->reference_number = static::generateReferenceNumber();
            }
        });
    }

    /** Format: TXN-20261102-0001 (sequence restarts each day). */
    public static function generateReferenceNumber(): string
    {
        return static::nextSequentialNumber('reference_number', 'TXN-'.now()->format('Ymd').'-', 4);
    }

    // ---- Relationships -------------------------------------------------

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'membership_transaction_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    // ---- Money ---------------------------------------------------------

    public function getAmountPaidAttribute(): float
    {
        // Reuse withSum('payments', 'amount') when the list query already loaded it.
        $sum = array_key_exists('payments_sum_amount', $this->attributes)
            ? $this->attributes['payments_sum_amount']
            : $this->payments()->sum('amount');

        return round((float) $sum, 2);
    }

    public function getBalanceAttribute(): float
    {
        return round(max(0, (float) $this->amount - $this->amount_paid), 2);
    }

    // ---- State rules ---------------------------------------------------

    /** A payment may only be recorded against a pending transaction with a balance. */
    public function canReceivePayment(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->balance > 0;
    }

    /** Only PAID transactions may be approved. */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /** Transactions that already received money cannot be cancelled. */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->payments()->exists();
    }
}
