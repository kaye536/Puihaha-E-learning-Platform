<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Notifications\PortalInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Creates and invites member-portal logins. A member's login is created when
 * their application is approved (or when staff send an invitation manually).
 */
class PortalAccountService
{
    public const CREATED = 'created';
    public const RESENT = 'resent';
    public const PENDING = 'pending';
    public const ALREADY_ACTIVE = 'already_active';
    public const EMAIL_TAKEN = 'email_taken';

    /**
     * Ensure the member has a login and an invitation to set a password.
     * Returns one of the constants above.
     */
    public function invite(Member $member, bool $resend = false): string
    {
        $user = $member->user;

        if ($user) {
            if ($user->portal_activated_at) {
                return self::ALREADY_ACTIVE;
            }
            if (! $resend) {
                return self::PENDING; // an invitation was already sent; do not spam on later approvals
            }
        } else {
            // The portal login uses the member's email; staff accounts own their own addresses.
            if (User::where('email', $member->email)->exists()) {
                return self::EMAIL_TAKEN;
            }

            $user = User::create([
                'name' => $member->full_name,
                'email' => $member->email,
                'password' => Str::random(40), // unusable until the member sets their own
                'role' => 'member',
                'is_active' => true,
                'member_id' => $member->id,
            ]);
        }

        // On-demand notification keeps the invitation independent of the user's session state.
        $user->notify(new PortalInvitation);

        return $user->wasRecentlyCreated ? self::CREATED : self::RESENT;
    }
}
