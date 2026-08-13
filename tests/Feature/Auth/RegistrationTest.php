<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'username' => 'test_user',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'username' => 'test_user',
    ]);
});

test('registration requires a unique username matching the allowed format', function () {
    User::factory()->create(['username' => 'taken_name']);

    $this->post(route('register.store'), [
        'name' => 'Taken User',
        'username' => 'taken_name',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    $this->post(route('register.store'), [
        'name' => 'Bad Format',
        'username' => 'Bad-Name!',
        'email' => 'bad@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');

    $this->post(route('register.store'), [
        'name' => 'Too Short',
        'username' => 'ab',
        'email' => 'short@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
});
