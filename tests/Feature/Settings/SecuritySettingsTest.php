<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('security page exposes email and linked accounts with nicknames', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'gh-1',
        'provider_nickname' => 'octocat',
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Security')
            ->where('email', $user->email)
            ->where('linkedAccounts', [
                ['provider' => 'github', 'nickname' => 'octocat'],
            ]));
});

test('email can be updated from the security tab', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->patch(route('user-email.update'), ['email' => 'new-address@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect($user->refresh()->email)->toBe('new-address@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email update requires a recent password confirmation', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->patch(route('user-email.update'), ['email' => 'new-address@example.com'])
        ->assertRedirect(route('password.confirm'));

    expect($user->refresh()->email)->not->toBe('new-address@example.com');
});

test('profile update no longer changes email', function () {
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'title' => 'Maker',
            'email' => 'hacked@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('original@example.com');
    expect($user->refresh()->name)->toBe('Renamed');
});

test('oauth provider can be linked from the security tab', function () {
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 'link-id-1',
        'nickname' => 'linker-handle',
        'email' => 'linker@example.com',
        'token' => 'tok',
    ]));

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('oauth.link', ['provider' => 'github']))
        ->assertRedirect();

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('security.edit'));

    expect($user->refresh()->oauthAccounts()->where('provider', 'github')->exists())->toBeTrue();
    expect($user->oauthAccounts()->first()->provider_nickname)->toBe('linker-handle');
    $this->assertAuthenticatedAs($user);
});

test('link initiation answers inertia requests with an external location redirect', function () {
    Socialite::fake('github');

    $user = User::factory()->create(['email_verified_at' => now()]);
    $version = (new HandleInertiaRequests)->version(new Request);

    // A plain 302 to the provider cannot be followed by an XHR (CORS), so
    // Inertia requests must receive the 409 + X-Inertia-Location contract.
    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('oauth.link', ['provider' => 'github']), [], [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ]);

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toContain('socialite.fake/github');
});

test('linking a provider already attached to another account is refused', function () {
    Socialite::fake('github', (new SocialiteUser)->map([
        'id' => 'taken-id',
        'email' => 'someone@example.com',
        'token' => 'tok',
    ]));

    $other = User::factory()->create();
    $other->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'taken-id',
        'linked_at' => now(),
    ]);

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('oauth.link', ['provider' => 'github']))
        ->assertRedirect();

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('security.edit'))
        ->assertSessionHas('inertia.flash_data', [
            'toast' => [
                'type' => 'error',
                'message' => 'This provider is already linked to another account.',
            ],
        ]);

    expect($user->refresh()->oauthAccounts()->count())->toBe(0);
});

test('oauth provider can be unlinked from the security tab', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->oauthAccounts()->create([
        'provider' => 'google',
        'provider_id' => 'g-1',
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('oauth.unlink', ['provider' => 'google']))
        ->assertRedirect(route('security.edit'));

    expect(OAuthAccount::where('user_id', $user->id)->where('provider', 'google')->exists())->toBeFalse();
    expect($user->fresh())->not->toBeNull();
});

test('passwordless creator can access the security tab without password confirmation', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'password' => null]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Security')
            ->where('hasPassword', false));
});

test('passwordless creator can set a password without a current password', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'password' => null]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Hash::check('new-strong-password', $user->refresh()->password))->toBeTrue();
});

test('password update still requires the current password for password creators', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
        ->assertSessionHasErrors('current_password');
});

test('last sign-in method cannot be unlinked by a passwordless creator', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'password' => null]);
    $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'gh-last',
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('oauth.unlink', ['provider' => 'github']))
        ->assertRedirect(route('security.edit'));

    expect(OAuthAccount::where('user_id', $user->id)->count())->toBe(1);
});

test('provider can be unlinked while another provider remains linked', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'password' => null]);
    $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'gh-2',
        'linked_at' => now(),
    ]);
    $user->oauthAccounts()->create([
        'provider' => 'google',
        'provider_id' => 'go-2',
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('oauth.unlink', ['provider' => 'github']))
        ->assertRedirect(route('security.edit'));

    expect($user->refresh()->oauthAccounts()->where('provider', 'google')->exists())->toBeTrue();
    expect($user->refresh()->oauthAccounts()->where('provider', 'github')->exists())->toBeFalse();
});
