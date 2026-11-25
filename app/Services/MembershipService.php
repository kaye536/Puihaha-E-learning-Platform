<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Carbon\Carbon;
use DomainException;
use App\Notifications\MembershipApplicationSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Business rules of the Transaction Processing System (TPS).
 * Controllers stay thin; every rule that changes state lives here.
 */
class MembershipService
{
    /**
     * Create an application or renewal transaction. The fee is copied from the
     * chosen level so later fee changes never alter existing transactions.
     */
    public function createTransaction(
        Member $member,
        string $type,
        MembershipLevel $level,
        ?string $remarks = null,
        ?int $userId = null,
        ?float $latitude = null,
        ?float $longitude = null,
    ): MembershipTransaction {
        if ($member->isInactive()) {
            throw new DomainException('This member is deactivated. Re-activate the member first.');
        }

        if ($member->hasOpenTransaction()) {
            throw new DomainException('This member already has a pending transaction. Complete or cancel it first.');
        }

        if ($type === MembershipTransaction::TYPE_APPLICATION && $member->membership_expiry !== null) {
            throw new DomainException('This member already has an approved membership. Create a renewal instead.');
        }

        if ($type === MembershipTransaction::TYPE_RENEWAL && $member->membership_expiry === null) {
            throw new DomainException('This member has no approved membership yet. Create an application first.');
        }

        return DB::transaction(function () use ($member, $level, $type, $remarks, $userId, $latitude, $longitude) {
            $transaction = MembershipTransaction::create([
                'member_id' => $member->id,
                'membership_level_id' => $level->id,
                'type' => $type,
                'amount' => $level->fee,
                'status' => MembershipTransaction::STATUS_PENDING,
                'transaction_date' => today(),
                'remarks' => $remarks,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'processed_by' => $userId,
            ]);

            // Queued and delivered only after the outermost DB transaction commits.
            Notification::route('mail', $member->email)
                ->notify(new MembershipApplicationSubmitted($transaction));

            $transaction->update(['verification_sent_at' => now()]);

            return $transaction;
        });
    }

    /**
     * Record a payment. The amount can never exceed the remaining balance and the
     * transaction becomes PAID automatically once it is fully settled.
     */
    public function recordPayment(MembershipTransaction $transaction, array $data, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            // Lock the row so two simultaneous payments cannot overpay.
            $locked = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (! $locked->canReceivePayment()) {
                throw new DomainException('Payments can only be recorded for pending transactions with an unpaid balance.');
            }

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new DomainException('The payment amount must be greater than zero.');
            }

            if ($amount > $locked->balance) {
                throw new DomainException('The payment amount exceeds the remaining balance of '
                    .\App\Support\Format::money($locked->balance).'.');
            }

            $payment = Payment::create([
                'membership_transaction_id' => $locked->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'],
                'reference_note' => $data['reference_note'] ?? null,
                'received_by' => $userId,
            ]);

            if ($locked->fresh()->balance <= 0) {
                $locked->update(['status' => MembershipTransaction::STATUS_PAID]);
            }

            return $payment;
        });
    }

    /** Only PAID transactions may be approved; approval activates the membership. */
    public function approve(MembershipTransaction $transaction): MembershipTransaction
    {
        $approved = DB::transaction(function () use ($transaction) {
            $locked = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (! $locked->canBeApproved()) {
                throw new DomainException('Only transactions with status "Paid" can be approved.');
            }

            $member = Member::query()->lockForUpdate()->findOrFail($locked->member_id);

            if ($member->isInactive()) {
                throw new DomainException('This member is deactivated. Re-activate the member before approving.');
            }

            $level = $locked->level;

            // A renewal made while still active continues after the current expiry date.
            $start = Carbon::today();
            if ($locked->type === MembershipTransaction::TYPE_RENEWAL
                && $member->membership_expiry
                && $member->membership_expiry->gte(today())) {
                $start = $member->membership_expiry->copy()->addDay();
            }

            $end = $start->copy()->addMonthsNoOverflow($level->validity_months)->subDay();

            $locked->update([
                'status' => MembershipTransaction::STATUS_APPROVED,
                'period_start' => $start,
                'period_end' => $end,
                'approved_at' => now(),
            ]);

            $member->update([
                'membership_level_id' => $level->id,
                'membership_start' => $member->membership_start ?? $start,
                'membership_expiry' => $end,
                'status' => Member::STATUS_ACTIVE,
            ]);

            return $locked->fresh();
        });

        // First approval: give the member a portal login and email them an invitation to set a password.
        // A problem here must never undo or block the approval, so it is reported and swallowed.
        rescue(fn () => app(PortalAccountService::class)->invite($approved->member), report: true);

        return $approved;
    }

    /** A transaction can only be cancelled while pending and without payments. */
    public function cancel(MembershipTransaction $transaction): MembershipTransaction
    {
        if (! $transaction->canBeCancelled()) {
            throw new DomainException('Only pending transactions without payments can be cancelled.');
        }

        $transaction->update(['status' => MembershipTransaction::STATUS_CANCELLED]);

        return $transaction;
    }

    /** Mark lapsed approved transactions and members as expired. */
    public function syncExpirations(): void
    {
        MembershipTransaction::query()
            ->where('status', MembershipTransaction::STATUS_APPROVED)
            ->whereDate('period_end', '<', today())
            ->update(['status' => MembershipTransaction::STATUS_EXPIRED]);

        Member::query()
            ->where('status', Member::STATUS_ACTIVE)
            ->whereDate('membership_expiry', '<', today())
            ->update(['status' => Member::STATUS_EXPIRED]);
    }
}
