<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->level = MembershipLevel::create(['name' => 'Gold', 'fee' => 1000, 'validity_months' => 12]);
});

function as_role(string $role): User
{
    $user = User::factory()->role($role)->create();
    test()->actingAs($user);

    return $user;
}

dataset('screen access', function () {
    $all = ['dashboard', 'members.index', 'members.create', 'categories.index', 'categories.create',
        'levels.index', 'levels.create', 'transactions.index', 'transactions.create',
        'payments.index', 'payments.create', 'users.index', 'users.create'];

    $allowed = [
        'admin' => $all,
        'registrar' => ['dashboard', 'members.index', 'members.create', 'categories.index', 'levels.index',
            'transactions.index', 'transactions.create', 'payments.index'],
        'cashier' => ['dashboard', 'members.index', 'transactions.index', 'payments.index', 'payments.create'],
        'viewer' => ['dashboard', 'members.index', 'categories.index', 'levels.index',
            'transactions.index', 'payments.index'],
    ];

    foreach ($allowed as $role => $routes) {
        yield $role => [$role, $routes, array_values(array_diff($all, $routes))];
    }
});

test('each role can open only the screens it is allowed to', function (string $role, array $allowed, array $denied) {
    as_role($role);

    foreach ($allowed as $route) {
        $this->get(route($route))->assertOk();
    }
    foreach ($denied as $route) {
        $this->get(route($route))->assertForbidden();
    }
})->with('screen access');

test('guests are redirected to the login page', function () {
    $this->get(route('members.index'))->assertRedirect(route('login'));
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('a viewer cannot change anything', function () {
    as_role('viewer');

    $this->post(route('members.store'), [])->assertForbidden();
    $this->post(route('transactions.store'), [])->assertForbidden();
    $this->post(route('payments.store'), [])->assertForbidden();
    $this->post(route('categories.store'), [])->assertForbidden();
    $this->post(route('levels.store'), [])->assertForbidden();
    $this->post(route('users.store'), [])->assertForbidden();
});

test('a cashier can record payments but cannot approve or cancel transactions', function () {
    Notification::fake();
    $member = Member::create([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana@example.test',
        'membership_category_id' => $this->category->id, 'membership_level_id' => $this->level->id,
        'status' => Member::STATUS_PENDING,
    ]);
    $transaction = app(MembershipService::class)->createTransaction($member, 'application', $this->level);

    as_role('cashier');

    $this->post(route('transactions.approve', $transaction))->assertForbidden();
    $this->post(route('transactions.cancel', $transaction))->assertForbidden();
    // Allowed to reach the action: fails validation (redirect), not authorization.
    $this->post(route('payments.store'), [])->assertSessionHasErrors();
});

test('a registrar can approve transactions but cannot record payments', function () {
    Notification::fake();
    $member = Member::create([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana@example.test',
        'membership_category_id' => $this->category->id, 'membership_level_id' => $this->level->id,
        'status' => Member::STATUS_PENDING,
    ]);
    $transaction = app(MembershipService::class)->createTransaction($member, 'application', $this->level);

    as_role('registrar');

    $this->post(route('payments.store'), [])->assertForbidden();
    $this->post(route('categories.store'), ['name' => 'X'])->assertForbidden();
    $this->post(route('transactions.cancel', $transaction))->assertSessionHasNoErrors()->assertStatus(302);
});

test('the menu only shows what the role may use', function () {
    as_role('viewer');
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('users.index'), false)
        ->assertDontSee(route('members.create'), false);

    as_role('admin');
    $this->get(route('dashboard'))->assertSee(route('users.index'), false)->assertSee('Administrator');
});

test('deactivated accounts cannot sign in', function () {
    User::factory()->inactive()->create(['email' => 'off@example.test', 'password' => 'secret-pass']);

    $this->post(route('login.attempt'), ['email' => 'off@example.test', 'password' => 'secret-pass'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an account deactivated mid-session is signed out on the next request', function () {
    $user = as_role('registrar');
    $this->get(route('dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('an administrator can create a staff account with a role', function () {
    as_role('admin');

    $this->post(route('users.store'), [
        'name' => 'Carlo Cashier', 'email' => 'carlo@example.test', 'role' => 'cashier',
        'password' => 'long-password-1', 'password_confirmation' => 'long-password-1', 'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    $created = User::where('email', 'carlo@example.test')->firstOrFail();
    expect($created->role)->toBe('cashier')
        ->and($created->is_active)->toBeTrue()
        ->and(Hash::check('long-password-1', $created->password))->toBeTrue()
        ->and($created->can('payments.record'))->toBeTrue()
        ->and($created->can('members.manage'))->toBeFalse();
});

test('unknown roles and weak passwords are rejected', function () {
    as_role('admin');

    $this->post(route('users.store'), [
        'name' => 'X', 'email' => 'x@example.test', 'role' => 'superuser',
        'password' => 'short', 'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['role', 'password']);
});

test('an administrator can change another account role and deactivate it', function () {
    as_role('admin');
    $other = User::factory()->role('viewer')->create();

    $this->put(route('users.update', $other), [
        'name' => $other->name, 'email' => $other->email, 'role' => 'registrar', 'is_active' => '1',
    ])->assertRedirect(route('users.index'));
    expect($other->fresh()->role)->toBe('registrar');

    $this->patch(route('users.toggle-active', $other))->assertSessionHas('success');
    expect($other->fresh()->is_active)->toBeFalse();
});

test('administrators cannot demote or deactivate themselves', function () {
    $admin = as_role('admin');

    $this->put(route('users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email, 'role' => 'viewer', 'is_active' => '1',
    ])->assertSessionHasErrors('role');
    $this->patch(route('users.toggle-active', $admin))->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe('admin')->and($admin->fresh()->is_active)->toBeTrue();
});

test('only administrators can reach staff management', function () {
    foreach (['registrar', 'cashier', 'viewer'] as $role) {
        as_role($role);
        $this->get(route('users.index'))->assertForbidden();
    }
});

test('the default role for new accounts is least privilege', function () {
    expect(config('rbac.default_role'))->toBe('viewer');
    expect(config('rbac.roles.viewer.permissions'))->not->toContain('members.manage', 'payments.record', 'users.manage');
});
