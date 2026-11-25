<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Member extends Model
{
    use GeneratesSequentialNumber;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'membership_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'birthdate',
        'membership_category_id',
        'membership_level_id',
        'status',
        'membership_start',
        'membership_expiry',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'membership_start' => 'date',
            'membership_expiry' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (blank($member->membership_number)) {
                $member->membership_number = static::generateMembershipNumber();
            }
        });
    }

    /** Format: MEM-2026-00001 */
    public static function generateMembershipNumber(): string
    {
        return static::nextSequentialNumber('membership_number', 'MEM-'.now()->format('Y').'-', 5);
    }

    // ---- Relationships -------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    /** The member's portal login, if one has been created. */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            MembershipTransaction::class,
            'member_id',
            'membership_transaction_id'
        );
    }

    // ---- Accessors -----------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    // ---- Scopes --------------------------------------------------------

    /** Search by name, membership number or email. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('membership_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");

            // "Juan Dela Cruz": every word must match either the first or the last name.
            $words = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > 1) {
                $q->orWhere(function (Builder $names) use ($words) {
                    foreach ($words as $word) {
                        $names->where(function (Builder $w) use ($word) {
                            $w->where('first_name', 'like', "%{$word}%")
                                ->orWhere('last_name', 'like', "%{$word}%");
                        });
                    }
                });
            }
        });
    }

    // ---- Helpers -------------------------------------------------------

    /** Is there a transaction still waiting to be paid or approved? */
    public function hasOpenTransaction(): bool
    {
        return $this->transactions()
            ->whereIn('status', [MembershipTransaction::STATUS_PENDING, MembershipTransaction::STATUS_PAID])
            ->exists();
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /** Status a member should return to when re-activated. */
    public function restoredStatus(): string
    {
        if (! $this->membership_expiry) {
            return self::STATUS_PENDING;
        }

        return $this->membership_expiry->lt(today()) ? self::STATUS_EXPIRED : self::STATUS_ACTIVE;
    }
}
