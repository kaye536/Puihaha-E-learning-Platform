<?php

namespace App\Notifications;

use App\Models\Member;
use App\Support\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome / confirmation email sent to a newly registered member.
 * Queued, and only delivered after the registration database transaction commits.
 */
class MemberRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Member $member)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $member = $this->member->loadMissing(['category', 'level']);

        return (new MailMessage)
            ->subject('Welcome to '.config('membership.organization').' — registration received')
            ->greeting('Welcome, '.$member->first_name.'!')
            ->line('Thank you for registering. Your membership record has been created.')
            ->line('**Membership number:** '.$member->membership_number)
            ->line('**Category:** '.($member->category?->name ?? '—'))
            ->line('**Level:** '.($member->level?->name ?? '—')
                .($member->level ? ' ('.Format::money($member->level->fee).')' : ''))
            ->line('**Status:** Pending — it becomes active once your membership is approved and paid.')
            ->line('Please keep your membership number for your records.')
            ->salutation('Warm regards, '.config('membership.organization'));
    }
}
