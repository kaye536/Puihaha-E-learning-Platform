<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->level = MembershipLevel::create(['name' => 'Gold', 'fee' => 1000, 'validity_months' => 12]);
    $this->actingAs($this->admin);
});

function memberPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'membership_category_id' => test()->category->id,
        'membership_level_id' => test()->level->id,
        'create_application' => '1',
    ], $overrides);
}

test('registering a member generates a unique membership number and an application', function () {
    $this->post(route('members.store'), memberPayload())->assertRedirect();

    $member = Member::first();
    expect($member->membership_number)->toStartWith('MEM-'.now()->format('Y').'-')
        ->and($member->status)->toBe('pending');

    $transaction = $member->transactions()->first();
    expect($transaction->type)->toBe('application')
        ->and($transaction->status)->toBe('pending')
        ->and((float) $transaction->amount)->toBe(1000.0)
        ->and($transaction->reference_number)->toStartWith('TXN-');
});

test('duplicate membership number and email are rejected', function () {
    $this->post(route('members.store'), memberPayload(['membership_number' => 'MEM-CUSTOM-1']));

    $this->post(route('members.store'), memberPayload([
        'email' => 'other@example.com',
        'membership_number' => 'MEM-CUSTOM-1',
    ]))->assertSessionHasErrors('membership_number');

    $this->post(route('members.store'), memberPayload(['membership_number' => '']))
        ->assertSessionHasErrors('email');
});

test('members can be searched by name, category and level', function () {
    $this->post(route('members.store'), memberPayload());

    $this->get(route('members.index', ['q' => 'Juan Cruz']))->assertSee('Dela Cruz');
    $this->get(route('members.index', ['q' => 'nobody']))->assertDontSee('Dela Cruz');
    $this->get(route('members.index', ['category' => $this->category->id]))->assertSee('Dela Cruz');
    $this->get(route('members.index', ['level' => $this->level->id + 99]))->assertDontSee('Dela Cruz');
});

test('payments cannot exceed the balance and approval requires full payment', function () {
    $this->post(route('members.store'), memberPayload());
    $transaction = MembershipTransaction::first();

    // Cannot approve an unpaid transaction
    $this->post(route('transactions.approve', $transaction))->assertSessionHas('error');
    expect($transaction->fresh()->status)->toBe('pending');

    // Overpayment is rejected
    $this->post(route('payments.store'), [
        'membership_transaction_id' => $transaction->id,
        'amount' => 1500,
        'payment_method' => 'cash',
        'payment_date' => today()->format('Y-m-d'),
    ])->assertSessionHasErrors('amount');

    // Partial payment keeps the transaction pending
    $this->post(route('payments.store'), [
        'membership_transaction_id' => $transaction->id,
        'amount' => 400,
        'payment_method' => 'cash',
        'payment_date' => today()->format('Y-m-d'),
    ]);
    expect($transaction->fresh()->status)->toBe('pending');
    $this->post(route('transactions.approve', $transaction))->assertSessionHas('error');

    // Final payment marks it paid
    $this->post(route('payments.store'), [
        'membership_transaction_id' => $transaction->id,
        'amount' => 600,
        'payment_method' => 'gcash',
        'payment_date' => today()->format('Y-m-d'),
    ]);
    expect($transaction->fresh()->status)->toBe('paid')
        ->and(Payment::count())->toBe(2)
        ->and(Payment::first()->receipt_number)->toStartWith('OR-');

    // Approval activates the membership
    $this->post(route('transactions.approve', $transaction))->assertSessionHas('success');
    $member = Member::first();
    expect($transaction->fresh()->status)->toBe('approved')
        ->and($member->status)->toBe('active')
        ->and($member->membership_expiry->toDateString())
        ->toBe(today()->addMonthsNoOverflow(12)->subDay()->toDateString());
});

test('a payment cannot be recorded for a cancelled transaction', function () {
    $this->post(route('members.store'), memberPayload());
    $transaction = MembershipTransaction::first();

    $this->post(route('transactions.cancel', $transaction));
    expect($transaction->fresh()->status)->toBe('cancelled');

    $this->post(route('payments.store'), [
        'membership_transaction_id' => $transaction->id,
        'amount' => 100,
        'payment_method' => 'cash',
        'payment_date' => today()->format('Y-m-d'),
    ])->assertSessionHasErrors('membership_transaction_id');
});

test('renewal extends an active membership and expired memberships are flagged', function () {
    $this->post(route('members.store'), memberPayload());
    $member = Member::first();
    $first = MembershipTransaction::first();

    $this->post(route('payments.store'), [
        'membership_transaction_id' => $first->id, 'amount' => 1000,
        'payment_method' => 'cash', 'payment_date' => today()->format('Y-m-d'),
    ]);
    $this->post(route('transactions.approve', $first));
    $firstExpiry = $member->fresh()->membership_expiry->copy();

    // Applications are no longer allowed once approved, renewals are
    $this->post(route('transactions.store'), [
        'member_id' => $member->id, 'type' => 'application', 'membership_level_id' => $this->level->id,
    ])->assertSessionHasErrors('member_id');

    $this->post(route('transactions.store'), [
        'member_id' => $member->id, 'type' => 'renewal', 'membership_level_id' => $this->level->id,
    ])->assertSessionHasNoErrors();

    $renewal = MembershipTransaction::where('type', 'renewal')->first();
    $this->post(route('payments.store'), [
        'membership_transaction_id' => $renewal->id, 'amount' => 1000,
        'payment_method' => 'cash', 'payment_date' => today()->format('Y-m-d'),
    ]);
    $this->post(route('transactions.approve', $renewal));

    expect($member->fresh()->membership_expiry->toDateString())
        ->toBe($firstExpiry->copy()->addDay()->addMonthsNoOverflow(12)->subDay()->toDateString());

    // Simulate lapse
    $member->update(['membership_expiry' => today()->subDay()]);
    $first->update(['period_end' => today()->subDay()]);
    $this->get(route('dashboard'))->assertOk();

    expect($member->fresh()->status)->toBe('expired')
        ->and($first->fresh()->status)->toBe('expired');
});

test('categories and levels in use cannot be deleted; members with transactions cannot be deleted', function () {
    $this->post(route('members.store'), memberPayload());
    $member = Member::first();

    $this->delete(route('categories.destroy', $this->category))->assertSessionHas('error');
    $this->delete(route('levels.destroy', $this->level))->assertSessionHas('error');
    $this->delete(route('members.destroy', $member))->assertSessionHas('error');
    expect(Member::count())->toBe(1);

    // Deactivate / reactivate
    $this->patch(route('members.toggle-status', $member));
    expect($member->fresh()->status)->toBe('inactive');
    $this->patch(route('members.toggle-status', $member));
    expect($member->fresh()->status)->toBe('pending');
});

test('printable pages and history render', function () {
    $this->post(route('members.store'), memberPayload());
    $member = Member::first();
    $transaction = MembershipTransaction::first();
    $this->post(route('payments.store'), [
        'membership_transaction_id' => $transaction->id, 'amount' => 1000,
        'payment_method' => 'cash', 'payment_date' => today()->format('Y-m-d'),
    ]);
    $payment = Payment::first();

    $this->get(route('members.history', $member))->assertOk()->assertSee($transaction->reference_number);
    $this->get(route('members.record', $member))->assertOk()->assertSee($member->membership_number);
    $this->get(route('payments.show', $payment))->assertOk()->assertSee($payment->receipt_number);
    $this->get(route('transactions.show', $transaction))->assertOk();
    $this->get(route('transactions.index'))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('payments.create'))->assertOk();
    $this->get(route('transactions.create'))->assertOk();
    $this->get(route('categories.index'))->assertOk();
    $this->get(route('levels.index'))->assertOk();
});
