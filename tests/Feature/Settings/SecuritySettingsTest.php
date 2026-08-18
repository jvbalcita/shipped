<?php

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('security page exposes email and linked providers', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'gh-1',
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Security')
            ->where('email', $user->email)
            ->where('linkedProviders', ['github']));
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
    $this->assertAuthenticatedAs($user);
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
        ->assertRedirect(route('security.edit'));

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
