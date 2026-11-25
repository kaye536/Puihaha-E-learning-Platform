<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** Invites a member to choose a password and open their membership portal. */
class PortalInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public const VALID_DAYS = 7;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('portal.setup', now()->addDays(self::VALID_DAYS), ['user' => $notifiable->id]);

        return (new MailMessage)
            ->subject('Your member portal is ready')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You can now sign in to the member portal to see your membership status, expiry date, payment receipts and to request a renewal.')
            ->line('Your username is **'.$notifiable->email.'**. Choose your password with the button below.')
            ->action('Set my password', $url)
            ->line('This link works once and expires in '.self::VALID_DAYS.' days. If it has expired, ask the membership office to send a new one.')
            ->salutation(config('membership.organization'));
    }
}
