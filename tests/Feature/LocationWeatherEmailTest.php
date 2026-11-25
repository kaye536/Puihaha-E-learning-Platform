<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Notifications\MembershipApplicationSubmitted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->level = MembershipLevel::create(['name' => 'Gold', 'fee' => 1000, 'validity_months' => 12]);
    $this->member = Member::create([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana@example.test',
        'membership_category_id' => $this->category->id,
        'membership_level_id' => $this->level->id,
        'status' => Member::STATUS_PENDING,
    ]);
});

test('a transaction stores its map location and queues the verification email', function () {
    Notification::fake();

    $this->post(route('transactions.store'), [
        'member_id' => $this->member->id,
        'type' => 'application',
        'membership_level_id' => $this->level->id,
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ])->assertSessionHasNoErrors();

    $transaction = MembershipTransaction::first();
    expect($transaction->latitude)->toBe(14.5995)
        ->and($transaction->longitude)->toBe(120.9842)
        ->and($transaction->verification_sent_at)->not->toBeNull();

    Notification::assertSentOnDemand(
        MembershipApplicationSubmitted::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ana@example.test'
            && $notification->transaction->is($transaction)
    );
});

test('the location is optional', function () {
    Notification::fake();

    $this->post(route('transactions.store'), [
        'member_id' => $this->member->id,
        'type' => 'application',
        'membership_level_id' => $this->level->id,
    ])->assertSessionHasNoErrors();

    expect(MembershipTransaction::first()->hasLocation())->toBeFalse();
});

test('invalid or incomplete coordinates are rejected', function () {
    $base = ['member_id' => $this->member->id, 'type' => 'application', 'membership_level_id' => $this->level->id];

    $this->post(route('transactions.store'), $base + ['latitude' => 120, 'longitude' => 10])
        ->assertSessionHasErrors('latitude');
    $this->post(route('transactions.store'), $base + ['latitude' => 10])
        ->assertSessionHasErrors('longitude');
    expect(MembershipTransaction::count())->toBe(0);
});

test('registering a member with an application stores the location and notifies', function () {
    Notification::fake();

    $this->post(route('members.store'), [
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.test',
        'membership_category_id' => $this->category->id,
        'membership_level_id' => $this->level->id,
        'create_application' => '1',
        'latitude' => 14.6, 'longitude' => 121.0,
    ])->assertSessionHasNoErrors();

    $transaction = Member::where('email', 'juan@example.test')->first()->transactions()->first();
    expect($transaction->latitude)->toBe(14.6);
    Notification::assertSentOnDemandTimes(MembershipApplicationSubmitted::class, 1);
    Notification::assertSentOnDemandTimes(MemberRegistered::class, 0); // one email per registration
});

test('registering a member without an application sends the welcome email', function () {
    Notification::fake();

    $this->post(route('members.store'), [
        'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@gmail.test',
        'membership_category_id' => $this->category->id,
        'membership_level_id' => $this->level->id,
        'create_application' => '0',
    ])->assertSessionHasNoErrors();

    expect(MembershipTransaction::count())->toBe(0);
    Notification::assertSentOnDemand(
        MemberRegistered::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'maria@gmail.test'
    );
    Notification::assertSentOnDemandTimes(MembershipApplicationSubmitted::class, 0);
});

test('the registration emails render with the member details', function () {
    Notification::fake();
    $welcome = (new MemberRegistered($this->member))->toMail($this->member)->render();
    expect((string) $welcome)->toContain($this->member->membership_number)->toContain('Welcome, Ana');

    $transaction = app(\App\Services\MembershipService::class)
        ->createTransaction($this->member, 'application', $this->level);
    $application = (new MembershipApplicationSubmitted($transaction))->toMail($this->member)->render();
    expect((string) $application)->toContain($transaction->reference_number)->toContain($this->member->membership_number);
});

test('the weather endpoint proxies OpenWeather without exposing the key', function () {
    config(['services.openweather.key' => 'secret-test-key']);
    Cache::flush();
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'Manila',
            'main' => ['temp' => 31.2, 'humidity' => 70],
            'weather' => [['description' => 'scattered clouds']],
        ]),
    ]);

    $this->getJson(route('weather.show', ['lat' => 14.5995, 'lon' => 120.9842]))
        ->assertOk()
        ->assertExactJson([
            'place' => 'Manila', 'temperature' => 31.2,
            'description' => 'scattered clouds', 'humidity' => 70,
        ]);

    Http::assertSent(fn ($r) => $r['appid'] === 'secret-test-key' && $r['units'] === 'metric');
});

test('the weather endpoint validates input and degrades gracefully', function () {
    config(['services.openweather.key' => 'secret-test-key']);
    Cache::flush();

    $this->getJson(route('weather.show', ['lat' => 999, 'lon' => 0]))->assertStatus(422);

    Http::fake(['api.openweathermap.org/*' => Http::response([], 401)]);
    $this->getJson(route('weather.show', ['lat' => 10, 'lon' => 10]))->assertStatus(502);

});

test('the transaction pages render the map partial', function () {
    Notification::fake();
    $this->get(route('transactions.create'))->assertOk()->assertSee('location-map', false);
    $this->get(route('members.create'))->assertOk()->assertSee('location-map', false);

    $this->post(route('transactions.store'), [
        'member_id' => $this->member->id, 'type' => 'application',
        'membership_level_id' => $this->level->id, 'latitude' => 14.5, 'longitude' => 121.1,
    ]);
    $this->get(route('transactions.show', MembershipTransaction::first()))
        ->assertOk()->assertSee('Applicant Location');
});
