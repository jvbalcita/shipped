<?php

use App\Models\ReservedUsername;
use App\Models\User;

test('user can change their username', function () {
    $user = User::factory()->create(['username' => 'oldname']);

    $response = $this
        ->actingAs($user)
        ->patch(route('username.update'), ['username' => 'newname']);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->username)->toBe('newname');
    expect(ReservedUsername::where('username', 'oldname')->exists())->toBeTrue();
});

test('changed username is reserved for 30 days', function () {
    $user = User::factory()->create(['username' => 'retiring']);

    $this->actingAs($user)
        ->patch(route('username.update'), ['username' => 'fresh']);

    $reservation = ReservedUsername::where('username', 'retiring')->first();

    expect($reservation)->not->toBeNull();
    expect($reservation->expires_at->greaterThan(now()->addDays(29)))->toBeTrue();
    expect($reservation->expires_at->lessThan(now()->addDays(31)))->toBeTrue();
});

test('registration rejects a reserved username', function () {
    ReservedUsername::create([
        'username' => 'taken',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->post(route('register'), [
        'name' => 'New User',
        'username' => 'taken',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('username');
    expect(User::where('username', 'taken')->exists())->toBeFalse();
});

test('an expired username reservation becomes available again', function () {
    ReservedUsername::create([
        'username' => 'released',
        'expires_at' => now()->subDay(),
    ]);

    $user = User::factory()->create(['username' => 'current']);

    $response = $this
        ->actingAs($user)
        ->patch(route('username.update'), ['username' => 'released']);

    $response->assertSessionHasNoErrors();
    expect($user->refresh()->username)->toBe('released');
});

test('username change is rate limited to once per cooldown', function () {
    $user = User::factory()->create(['username' => 'first']);

    $this->actingAs($user)
        ->patch(route('username.update'), ['username' => 'second'])
        ->assertSessionHasNoErrors();

    $response = $this
        ->actingAs($user)
        ->patch(route('username.update'), ['username' => 'third']);

    $response->assertSessionHasErrors('username');
    expect($user->refresh()->username)->toBe('second');
});

test('purge command releases expired reservations', function () {
    ReservedUsername::create([
        'username' => 'expired_one',
        'expires_at' => now()->subDay(),
    ]);
    ReservedUsername::create([
        'username' => 'active_one',
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('shipped:purge-reserved-usernames')
        ->assertSuccessful();

    expect(ReservedUsername::where('username', 'expired_one')->exists())->toBeFalse();
    expect(ReservedUsername::where('username', 'active_one')->exists())->toBeTrue();
});
