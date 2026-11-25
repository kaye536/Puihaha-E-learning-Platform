<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Notifications\MembershipApplicationSubmitted;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->level = MembershipLevel::create(['name' => 'Gold', 'fee' => 1000, 'validity_months' => 12]);
});

function applicationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'email' => 'ana@gmail.test',
        'membership_category_id' => MembershipCategory::first()->id,
        'membership_level_id' => MembershipLevel::first()->id,
        'latitude' => 14.5995,
        'longitude' => 120.9842,
        'consent' => '1',
        'website' => '',
        'form_token' => Crypt::encryptString((string) now()->subSeconds(30)->timestamp),
    ], $overrides);
}

test('guests can open the public application page', function () {
    $this->get(route('membership.apply'))
        ->assertOk()
        ->assertSee('Apply for')
        ->assertSee('Regular')
        ->assertSee('Gold')
        ->assertSee('location-map', false);
});

test('a guest application creates a pending member, a transaction with location and the email', function () {
    Notification::fake();

    $this->post(route('membership.apply.store'), applicationPayload())
        ->assertRedirect(route('membership.apply.thanks'));

    $member = Member::where('email', 'ana@gmail.test')->firstOrFail();
    expect($member->status)->toBe(Member::STATUS_PENDING);

    $transaction = $member->transactions()->firstOrFail();
    expect($transaction->type)->toBe(MembershipTransaction::TYPE_APPLICATION)
        ->and($transaction->latitude)->toBe(14.5995)
        ->and($transaction->processed_by)->toBeNull()
        ->and($transaction->verification_sent_at)->not->toBeNull();

    Notification::assertSentOnDemand(
        MembershipApplicationSubmitted::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'ana@gmail.test'
    );
});

test('the thank-you page shows the reference once and then redirects back', function () {
    Notification::fake();

    $this->followingRedirects()->post(route('membership.apply.store'), applicationPayload())
        ->assertOk()
        ->assertSee('Thank you, Ana')
        ->assertSee(Member::first()->membership_number)
        ->assertSee(MembershipTransaction::first()->reference_number);

    $this->get(route('membership.apply.thanks'))->assertRedirect(route('membership.apply'));
});

test('the location is optional on the public form', function () {
    Notification::fake();

    $this->post(route('membership.apply.store'), applicationPayload(['latitude' => '', 'longitude' => '']))
        ->assertSessionHasNoErrors();

    expect(MembershipTransaction::first()->hasLocation())->toBeFalse();
});

test('consent, valid email and unique email are required', function () {
    Notification::fake();
    $this->withoutMiddleware(ThrottleRequests::class); // the limiter is covered by its own test

    $this->post(route('membership.apply.store'), applicationPayload(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post(route('membership.apply.store'), applicationPayload(['email' => 'not-an-email']))->assertSessionHasErrors('email');

    $this->post(route('membership.apply.store'), applicationPayload())->assertSessionHasNoErrors();
    $this->post(route('membership.apply.store'), applicationPayload())->assertSessionHasErrors('email');

    expect(Member::count())->toBe(1);
});

test('a filled honeypot is silently discarded', function () {
    Notification::fake();

    $this->post(route('membership.apply.store'), applicationPayload(['website' => 'http://spam.test']))
        ->assertRedirect(route('membership.apply.thanks'));

    expect(Member::count())->toBe(0);
    Notification::assertNothingSent();
});

test('submissions that are too fast or have no valid token are rejected', function () {
    Notification::fake();
    $this->withoutMiddleware(ThrottleRequests::class); // the limiter is covered by its own test

    $tooFast = Crypt::encryptString((string) now()->timestamp);
    $this->post(route('membership.apply.store'), applicationPayload(['form_token' => $tooFast]))
        ->assertSessionHasErrors('form_token');
    $this->post(route('membership.apply.store'), applicationPayload(['form_token' => 'garbage']))
        ->assertSessionHasErrors('form_token');
    $this->post(route('membership.apply.store'), applicationPayload(['form_token' => null]))
        ->assertSessionHasErrors('form_token');

    expect(Member::count())->toBe(0);
});

test('the public form is rate limited per IP address', function () {
    Notification::fake();

    foreach (range(1, 3) as $i) {
        $this->post(route('membership.apply.store'), applicationPayload(['email' => "user{$i}@gmail.test"]))
            ->assertRedirect(route('membership.apply.thanks'));
    }

    $this->post(route('membership.apply.store'), applicationPayload(['email' => 'user4@gmail.test']))
        ->assertStatus(429);

    expect(Member::count())->toBe(3);
});

test('weather works for guests on the public form', function () {
    config(['services.openweather.key' => 'secret-test-key']);
    Http::fake(['api.openweathermap.org/*' => Http::response([
        'name' => 'Manila', 'main' => ['temp' => 30, 'humidity' => 65], 'weather' => [['description' => 'clear sky']],
    ])]);

    $this->getJson(route('weather.show', ['lat' => 14.6, 'lon' => 121]))->assertOk()->assertJsonPath('place', 'Manila');
});
