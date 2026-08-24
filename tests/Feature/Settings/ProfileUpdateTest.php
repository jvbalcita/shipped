<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'title' => 'Indie Hacker',
            'location' => 'Berlin, DE',
            'links' => [
                ['type' => 'website', 'url' => 'https://example.com'],
                ['type' => 'github', 'url' => 'https://github.com/example'],
            ],
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->title)->toBe('Indie Hacker');
    expect($user->location)->toBe('Berlin, DE');
    expect($user->links)->toBe([
        ['type' => 'website', 'url' => 'https://example.com'],
        ['type' => 'github', 'url' => 'https://github.com/example'],
    ]);
});

test('profile accepts current developer link types', function () {
    $user = User::factory()->create();
    $links = [
        ['type' => 'x', 'url' => 'https://x.com/example'],
        ['type' => 'devto', 'url' => 'https://dev.to/example'],
        ['type' => 'hashnode', 'url' => 'https://hashnode.com/@example'],
        ['type' => 'stackoverflow', 'url' => 'https://stackoverflow.com/users/1/example'],
        ['type' => 'npm', 'url' => 'https://www.npmjs.com/~example'],
        ['type' => 'bluesky', 'url' => 'https://bsky.app/profile/example.bsky.social'],
        ['type' => 'codepen', 'url' => 'https://codepen.io/example'],
    ];

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'title' => 'Indie Hacker',
            'links' => $links,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->links)->toBe($links);
});

test('profile rejects invalid links and overlong fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'title' => str_repeat('a', 51),
            'location' => str_repeat('b', 81),
            'links' => [
                ['type' => 'myspace', 'url' => 'not-a-url'],
            ],
        ])
        ->assertSessionHasErrors(['title', 'location', 'links.0.type', 'links.0.url']);
});

test('public creator page renders title location and links', function () {
    $creator = User::factory()->create([
        'username' => 'maker',
        'title' => 'Designer',
        'location' => 'Remote',
        'links' => [
            ['type' => 'website', 'url' => 'https://maker.test'],
        ],
    ]);

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Creators/Show')
            ->where('creator.username', 'maker')
            ->where('creator.title', 'Designer')
            ->where('creator.location', 'Remote')
            ->where('creator.links.0.type', 'website')
            ->where('creator.links.0.url', 'https://maker.test'));
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'title' => 'Creator',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
