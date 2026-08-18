<?php

use App\Models\ReservedUsername;
use App\Models\User;

test('welcome page shows the generated handle for an unclaimed creator', function () {
    $user = User::factory()->create(['username_claimed_at' => null]);

    $this->actingAs($user)
        ->get(route('username.welcome'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/ClaimUsername')
            ->where('username', $user->username));
});

test('welcome page redirects creators who already claimed a username', function () {
    $user = User::factory()->create(['username_claimed_at' => now()]);

    $this->actingAs($user)
        ->get(route('username.welcome'))
        ->assertRedirect(route('profile.edit'));
});

test('unclaimed creator can claim a handle without reserving the generated one', function () {
    $user = User::factory()->create([
        'username' => 'auto_gen_1',
        'username_claimed_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('username.claim'), ['username' => 'chosen_handle'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->username)->toBe('chosen_handle');
    expect($user->username_claimed_at)->not->toBeNull();
    expect(ReservedUsername::where('username', 'auto_gen_1')->exists())->toBeFalse();
});

test('keeping the generated handle still counts as a claim', function () {
    $user = User::factory()->create([
        'username' => 'auto_gen_1',
        'username_claimed_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('username.claim'), ['username' => 'auto_gen_1'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->username)->toBe('auto_gen_1');
    expect($user->username_claimed_at)->not->toBeNull();
});

test('a username can only be claimed once', function () {
    $user = User::factory()->create(['username_claimed_at' => now(), 'username' => 'first_pick']);

    $this->actingAs($user)
        ->patch(route('username.claim'), ['username' => 'second_pick'])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->username)->toBe('first_pick');
});

test('claim rejects a handle that is already taken', function () {
    User::factory()->create(['username' => 'taken_handle']);
    $user = User::factory()->create(['username_claimed_at' => null]);

    $this->actingAs($user)
        ->patch(route('username.claim'), ['username' => 'taken_handle'])
        ->assertSessionHasErrors('username');

    expect($user->refresh()->username_claimed_at)->toBeNull();
});

test('manual registration marks the chosen username as claimed', function () {
    $this->post(route('register'), [
        'name' => 'Manual Creator',
        'username' => 'manual_pick',
        'email' => 'manual@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'manual@example.com')->first();

    expect($user->username_claimed_at)->not->toBeNull();
});

test('generated usernames skip reserved handles', function () {
    ReservedUsername::create([
        'username' => 'octocat',
        'expires_at' => now()->addDays(30),
    ]);

    expect(User::generateUniqueUsername('octocat'))->toBe('octocat_1');
});

test('short seeds are padded to the minimum handle length', function () {
    expect(User::generateUniqueUsername('jo'))->toBe('jo_');
});
