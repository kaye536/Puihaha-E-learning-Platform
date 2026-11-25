<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PortalInvitation;
use App\Services\MembershipService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->level = MembershipLevel::create(['name' => 'Gold', 'fee' => 1000, 'validity_months' => 12]);
});

/** An approved member (optionally with a portal login). */
function portalMember(array $overrides = [], bool $withLogin = true): Member
{
    $member = Member::create(array_merge([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana'.uniqid().'@example.test',
        'membership_category_id' => test()->category->id, 'membership_level_id' => test()->level->id,
        'status' => Member::STATUS_ACTIVE,
        'membership_start' => today()->subMonths(11), 'membership_expiry' => today()->addDays(30),
    ], $overrides));

    if ($withLogin) {
        User::factory()->role('member')->create([
            'name' => $member->full_name, 'email' => $member->email,
            'member_id' => $member->id, 'portal_activated_at' => now(),
        ]);
    }

    return $member->fresh();
}

function signInAsMember(Member $member): User
{
    $user = $member->user;
    test()->actingAs($user);

    return $user;
}

function payAndApprove(Member $member, string $type = 'application'): MembershipTransaction
{
    $service = app(MembershipService::class);
    $transaction = $service->createTransaction($member, $type, test()->level);
    $service->recordPayment($transaction, ['amount' => $transaction->amount, 'payment_method' => 'cash', 'payment_date' => today()->toDateString()]);

    return $service->approve($transaction->fresh());
}

// ---------------------------------------------------------------- accounts & invitation

test('approving a first application creates a member login and emails an invitation once', function () {
    Notification::fake();
    $member = portalMember(['status' => Member::STATUS_PENDING, 'membership_start' => null, 'membership_expiry' => null], withLogin: false);

    payAndApprove($member);

    $user = User::where('member_id', $member->id)->firstOrFail();
    expect($user->role)->toBe('member')->and($user->email)->toBe($member->email)->and($user->portal_activated_at)->toBeNull();
    Notification::assertSentTo($user, PortalInvitation::class);

    // A later renewal approval must not send another invitation.
    payAndApprove($member->fresh(), 'renewal');
    Notification::assertSentToTimes($user, PortalInvitation::class, 1);
});

test('no member login is created when a staff account already uses the email', function () {
    Notification::fake();
    $member = portalMember(['email' => 'shared@example.test', 'status' => Member::STATUS_PENDING, 'membership_start' => null, 'membership_expiry' => null], withLogin: false);
    User::factory()->role('registrar')->create(['email' => 'shared@example.test']);

    expect(fn () => payAndApprove($member))->not->toThrow(Throwable::class);
    expect(User::where('member_id', $member->id)->exists())->toBeFalse();
});

test('the invitation link lets the member choose a password once', function () {
    $member = portalMember(withLogin: false);
    $user = User::factory()->role('member')->create(['email' => $member->email, 'member_id' => $member->id, 'portal_activated_at' => null]);
    $url = URL::temporarySignedRoute('portal.setup', now()->addDay(), ['user' => $user->id]);

    $this->get($url)->assertOk()->assertSee($member->email);

    $this->post($url, ['password' => 'a-good-password', 'password_confirmation' => 'a-good-password'])
        ->assertRedirect(route('portal.index'));

    expect($user->fresh()->portal_activated_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);

    auth()->logout();
    $this->get($url)->assertRedirect(route('login')); // single use
});

test('tampered, expired or non-member invitation links are rejected', function () {
    $member = portalMember(withLogin: false);
    $user = User::factory()->role('member')->create(['email' => $member->email, 'member_id' => $member->id, 'portal_activated_at' => null]);

    $this->get(route('portal.setup', ['user' => $user->id]))->assertForbidden(); // unsigned
    $this->get(URL::temporarySignedRoute('portal.setup', now()->subMinute(), ['user' => $user->id]))->assertForbidden(); // expired

    $staff = User::factory()->role('registrar')->create();
    $this->get(URL::temporarySignedRoute('portal.setup', now()->addDay(), ['user' => $staff->id]))->assertNotFound();
});

test('staff can send or resend an invitation for an approved member', function () {
    Notification::fake();
    $member = portalMember(withLogin: false);
    $this->actingAs(User::factory()->role('registrar')->create());

    $this->post(route('members.portal-invite', $member))->assertSessionHas('success');
    $user = User::where('member_id', $member->id)->firstOrFail();
    Notification::assertSentTo($user, PortalInvitation::class);

    $this->post(route('members.portal-invite', $member))->assertSessionHas('success');
    Notification::assertSentToTimes($user, PortalInvitation::class, 2);

    $user->update(['portal_activated_at' => now()]);
    $this->post(route('members.portal-invite', $member))->assertSessionHas('error');
});

test('invitations are not sent for unapproved members and need the manage permission', function () {
    Notification::fake();
    $pending = portalMember(['status' => Member::STATUS_PENDING, 'membership_start' => null, 'membership_expiry' => null], withLogin: false);

    $this->actingAs(User::factory()->role('registrar')->create());
    $this->post(route('members.portal-invite', $pending))->assertSessionHas('error');

    $this->actingAs(User::factory()->role('cashier')->create());
    $this->post(route('members.portal-invite', portalMember(withLogin: false)))->assertForbidden();
    Notification::assertNothingSent();
});

// ---------------------------------------------------------------- sign-in & separation

test('a member lands on the portal after signing in', function () {
    $member = portalMember();
    $member->user->update(['password' => 'member-pass-1']);

    $this->post(route('login.attempt'), ['email' => $member->email, 'password' => 'member-pass-1'])
        ->assertRedirect(route('portal.index'));
    $this->get('/')->assertRedirect(route('portal.index'));
});

test('a member cannot open any staff screen or action', function () {
    signInAsMember(portalMember());

    foreach (['dashboard', 'members.index', 'members.create', 'transactions.index', 'payments.index',
        'categories.index', 'levels.index', 'users.index'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
    $this->post(route('members.store'), [])->assertForbidden();
    $this->post(route('payments.store'), [])->assertForbidden();
});

test('staff accounts without a member record cannot use the portal', function () {
    foreach (['admin', 'registrar', 'cashier', 'viewer'] as $role) {
        $this->actingAs(User::factory()->role($role)->create());
        $this->get(route('portal.index'))->assertForbidden();
    }
});

test('the member menu shows portal links and no staff links', function () {
    signInAsMember(portalMember());

    $this->get(route('portal.index'))
        ->assertOk()
        ->assertSee(route('portal.history'), false)
        ->assertDontSee(route('members.index'), false)
        ->assertDontSee(route('users.index'), false)
        ->assertDontSee(route('dashboard'), false);
});

// ---------------------------------------------------------------- data isolation

test('a member sees only their own record, history and receipts', function () {
    Notification::fake();
    $a = portalMember(['first_name' => 'Alice', 'last_name' => 'Aaa']);
    $b = portalMember(['first_name' => 'Bobby', 'last_name' => 'Bbb']);

    $service = app(MembershipService::class);
    $mine = $service->createTransaction($a, 'renewal', $this->level);
    $theirs = $service->createTransaction($b, 'renewal', $this->level);
    $myPayment = $service->recordPayment($mine, ['amount' => 400, 'payment_method' => 'cash', 'payment_date' => today()->toDateString()]);
    $theirPayment = $service->recordPayment($theirs, ['amount' => 400, 'payment_method' => 'cash', 'payment_date' => today()->toDateString()]);

    signInAsMember($a);

    $this->get(route('portal.index'))->assertOk()->assertSee($a->membership_number)->assertDontSee($b->membership_number)->assertDontSee('Bobby');
    $this->get(route('portal.history'))->assertOk()
        ->assertSee($mine->reference_number)->assertDontSee($theirs->reference_number)
        ->assertSee($myPayment->receipt_number)->assertDontSee($theirPayment->receipt_number);

    $this->get(route('portal.receipt', $myPayment))->assertOk()->assertSee($myPayment->receipt_number);
    $this->get(route('portal.receipt', $theirPayment))->assertNotFound();
});

// ---------------------------------------------------------------- renewal

test('a member can request a renewal when it is within the renewal window', function () {
    Notification::fake();
    $member = portalMember(['membership_expiry' => today()->addDays(30)]);
    signInAsMember($member);

    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])
        ->assertRedirect(route('portal.index'))->assertSessionHas('success');

    $renewal = $member->transactions()->firstOrFail();
    expect($renewal->type)->toBe('renewal')->and($renewal->status)->toBe('pending')
        ->and($renewal->processed_by)->toBeNull()->and((float) $renewal->amount)->toBe(1000.0);
    Notification::assertSentOnDemand(\App\Notifications\MembershipApplicationSubmitted::class);

    // Only one request at a time.
    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])->assertSessionHas('error');
    expect($member->transactions()->count())->toBe(1);
});

test('renewals are refused too early, before approval, or when deactivated', function () {
    Notification::fake();

    signInAsMember(portalMember(['membership_expiry' => today()->addDays(300)]));
    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])->assertSessionHas('error');

    signInAsMember(portalMember(['status' => Member::STATUS_PENDING, 'membership_start' => null, 'membership_expiry' => null]));
    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])->assertSessionHas('error');

    signInAsMember(portalMember(['status' => Member::STATUS_INACTIVE]));
    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])->assertSessionHas('error');

    expect(MembershipTransaction::count())->toBe(0);
});

test('an expired member can renew', function () {
    Notification::fake();
    $member = portalMember(['status' => Member::STATUS_EXPIRED, 'membership_expiry' => today()->subDays(10)]);
    signInAsMember($member);

    $this->get(route('portal.renew'))->assertOk()->assertSee('Request renewal');
    $this->post(route('portal.renew.store'), ['membership_level_id' => $this->level->id])->assertSessionHas('success');
    expect($member->transactions()->count())->toBe(1);
});

// ---------------------------------------------------------------- profile

test('a member can update their phone and address but nothing else', function () {
    $member = portalMember();
    signInAsMember($member);

    $this->patch(route('portal.profile.update'), [
        'phone' => '+63 917 123 4567', 'address' => 'Quezon City',
        'email' => 'hacker@example.test', 'first_name' => 'Hacked', 'membership_expiry' => '2099-01-01',
    ])->assertSessionHasNoErrors();

    $fresh = $member->fresh();
    expect($fresh->phone)->toBe('+63 917 123 4567')->and($fresh->address)->toBe('Quezon City')
        ->and($fresh->email)->toBe($member->email)->and($fresh->first_name)->toBe('Ana')
        ->and($fresh->membership_expiry->isSameDay($member->membership_expiry))->toBeTrue();

    $this->patch(route('portal.profile.update'), ['phone' => 'call me'])->assertSessionHasErrors('phone');
});

// ---------------------------------------------------------------- admin screens & housekeeping

test('member logins are managed from the member page, not Staff & Roles', function () {
    $member = portalMember();
    $this->actingAs(User::factory()->role('admin')->create(['name' => 'Boss Admin']));

    $this->get(route('users.index'))->assertOk()->assertDontSee($member->email);
    $this->get(route('users.edit', $member->user))->assertNotFound();
    $this->patch(route('users.toggle-active', $member->user))->assertNotFound();

    $this->post(route('users.store'), [
        'name' => 'X', 'email' => 'x@example.test', 'role' => 'member',
        'password' => 'long-password-1', 'password_confirmation' => 'long-password-1',
    ])->assertSessionHasErrors('role');
});

test('the member page shows the portal access status', function () {
    $member = portalMember();
    $this->actingAs(User::factory()->role('viewer')->create());

    $this->get(route('members.show', $member))->assertOk()->assertSee('Portal access')->assertSee('Active');
});

test('deleting a member with no transactions also removes their login', function () {
    $member = portalMember();
    $login = $member->user;
    $this->actingAs(User::factory()->role('registrar')->create());

    $this->delete(route('members.destroy', $member))->assertRedirect(route('members.index'));

    expect(User::find($login->id))->toBeNull();
});

// ---------------------------------------------------------------- forgot / reset password

test('forgot password emails a link to active accounts and reveals nothing otherwise', function () {
    Notification::fake();
    $member = portalMember();
    $inactive = User::factory()->inactive()->create(['email' => 'off@example.test']);

    $this->post(route('password.email'), ['email' => $member->email])->assertSessionHas('status');
    Notification::assertSentTo($member->user, ResetPassword::class);

    $this->post(route('password.email'), ['email' => 'nobody@example.test'])->assertSessionHas('status');
    $this->post(route('password.email'), ['email' => 'off@example.test'])->assertSessionHas('status');
    Notification::assertNotSentTo($inactive, ResetPassword::class);
});

test('a valid reset link sets a new password', function () {
    $member = portalMember();
    $member->user->update(['portal_activated_at' => null]);
    $token = Password::createToken($member->user);

    $this->post(route('password.update'), [
        'token' => $token, 'email' => $member->email,
        'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1',
    ])->assertRedirect(route('login'));

    expect(\Illuminate\Support\Facades\Hash::check('brand-new-pass-1', $member->user->fresh()->password))->toBeTrue()
        ->and($member->user->fresh()->portal_activated_at)->not->toBeNull();

    $this->post(route('password.update'), [
        'token' => 'wrong', 'email' => $member->email,
        'password' => 'another-pass-123', 'password_confirmation' => 'another-pass-123',
    ])->assertSessionHasErrors('email');
});
