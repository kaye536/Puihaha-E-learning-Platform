<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('guests see the public landing page at the root url', function () {
    $this->get('/')->assertOk()->assertSee('Launch and grow your membership');
});

test('the root url redirects signed-in administrators to the dashboard', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
});

test('an administrator can log in and view the dashboard', function () {
    $user = User::factory()->create(['password' => 'secret-pass']);

    $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'secret-pass'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertOk()->assertSee('Total members');
});

test('login fails with wrong credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});
