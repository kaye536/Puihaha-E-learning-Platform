<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use App\Support\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Verification email sent when a membership application/renewal is submitted.
 * Queued, and only dispatched after the surrounding database transaction commits.
 */
class MembershipApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MembershipTransaction $transaction)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->transaction->loadMissing(['member', 'level']);
        $type = ucfirst($transaction->type);

        return (new MailMessage)
            ->subject("Membership {$transaction->type} received")
            ->greeting('Hello '.$transaction->member->full_name.',')
            ->line("We received your membership {$transaction->type}.")
            ->line('**Membership number:** '.$transaction->member->membership_number)
            ->line('**Reference number:** '.$transaction->reference_number)
            ->line("**Type:** {$type}")
            ->line('**Level:** '.$transaction->level->name)
            ->line('**Amount due:** '.Format::money($transaction->amount))
            ->line('**Status:** Pending verification')
            ->line('Keep these numbers for your records.')
            ->salutation(config('membership.organization'));
    }
}
