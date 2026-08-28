<?php

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function oauthFakeUser(array $overrides = []): SocialiteUser
{
    return (new SocialiteUser)->map(array_merge([
        'id' => 'provider-id-123',
        'nickname' => 'octocat',
        'name' => 'Octo Cat',
        'email' => 'octo@example.com',
        'token' => 'token-abc',
        'refreshToken' => 'refresh-abc',
        'expiresIn' => 3600,
    ], $overrides));
}

test('oauth redirect sends the visitor to the provider', function () {
    config(['services.github.client_id' => 'test-client-id']);

    Socialite::fake('github');

    $response = $this->get(route('oauth.redirect', ['provider' => 'github']));

    $response->assertRedirectContains('socialite.fake/github');
});

test('unknown provider returns 404 on redirect', function () {
    $this->get(route('oauth.redirect', ['provider' => 'facebook']))
        ->assertNotFound();
});

test('unconfigured provider returns 404 on redirect', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('oauth.redirect', ['provider' => 'google']))
        ->assertNotFound();
});

test('login and register pages only offer configured providers', function () {
    config([
        'services.github.client_id' => 'test-client-id',
        'services.google.client_id' => null,
    ]);

    $login = $this->get(route('login'));

    $login->assertOk();
    $login->assertInertia(fn (AssertableInertia $page) => $page->where('oauthProviders', ['github']));

    $register = $this->get(route('register'));

    $register->assertOk();
    $register->assertInertia(fn (AssertableInertia $page) => $page->where('oauthProviders', ['github']));
});

test('new oauth user is created and logged in', function () {
    Socialite::fake('github', oauthFakeUser());

    $response = $this->get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(route('username.welcome'));
    $this->assertAuthenticated();

    $user = Auth::user();
    expect($user->email)->toBe('octo@example.com');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->password)->toBeNull();
    expect(OAuthAccount::where('provider', 'github')->where('provider_id', 'provider-id-123')->exists())->toBeTrue();
    expect($user->oauthAccounts()->first()->provider_nickname)->toBe('octocat');
});

test('existing provider link logs the user in without creating a duplicate', function () {
    $user = User::factory()->create(['email' => 'linked@example.com']);
    $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'existing-id',
        'linked_at' => now(),
    ]);

    Socialite::fake('github', oauthFakeUser([
        'id' => 'existing-id',
        'email' => 'linked@example.com',
    ]));

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('dashboard'));

    expect(Auth::id())->toBe($user->id);
    expect(User::where('email', 'linked@example.com')->count())->toBe(1);
});

test('logging in with the provider rotates an expired stored token', function () {
    $user = User::factory()->create(['email' => 'linked@example.com']);
    $account = $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'existing-id',
        'provider_token' => 'expired-token',
        'token_expires_at' => now()->subHours(2),
        'linked_at' => now()->subDays(2),
    ]);

    Socialite::fake('github', oauthFakeUser([
        'id' => 'existing-id',
        'email' => 'linked@example.com',
        'token' => 'fresh-token',
        'refreshToken' => 'fresh-refresh',
        'expiresIn' => 3600,
    ]));

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('dashboard'));

    $account->refresh();

    expect($account->provider_token)->toBe('fresh-token')
        ->and($account->provider_refresh_token)->toBe('fresh-refresh')
        ->and($account->token_expires_at->getTimestamp())->toBeGreaterThan(now()->getTimestamp());
});

test('a provider response without expiry clears a stale expiry timestamp', function () {
    $user = User::factory()->create(['email' => 'linked@example.com']);
    $account = $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'existing-id',
        'provider_token' => 'old-token',
        'token_expires_at' => now()->subHours(2),
        'linked_at' => now()->subDays(2),
    ]);

    Socialite::fake('github', oauthFakeUser([
        'id' => 'existing-id',
        'email' => 'linked@example.com',
        'token' => 'fresh-token',
        'refreshToken' => null,
        'expiresIn' => null,
    ]));

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('dashboard'));

    $account->refresh();

    expect($account->provider_token)->toBe('fresh-token')
        ->and($account->token_expires_at)->toBeNull();
});

test('re-linking an already linked provider refreshes its credentials instead of skipping', function () {
    $user = User::factory()->create();
    $account = $user->oauthAccounts()->create([
        'provider' => 'github',
        'provider_id' => 'provider-id-123',
        'provider_token' => 'stale-token',
        'token_expires_at' => now()->subHours(8),
        'linked_at' => now()->subDays(9),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('oauth.link', ['provider' => 'github']))
        ->assertRedirect();

    Socialite::fake('github', oauthFakeUser(['token' => 'fresh-token', 'expiresIn' => 3600]));

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('security.edit'));

    $account->refresh();

    expect($account->provider_token)->toBe('fresh-token')
        ->and(OAuthAccount::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('email collision without a provider link refuses auto-merge', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Socialite::fake('github', oauthFakeUser([
        'id' => 'some-other-id',
        'email' => 'taken@example.com',
    ]));

    $response = $this->get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    expect(OAuthAccount::count())->toBe(0);
});

test('declined authorization redirects back to login', function () {
    Socialite::fake('github');

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('oauth registration imports the provider avatar once', function () {
    Http::fake([
        'avatar.example.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    Socialite::fake('github', oauthFakeUser([
        'avatar' => 'https://avatar.example.com/octo.jpg',
    ]));

    $this->get(route('oauth.callback', ['provider' => 'github']))
        ->assertRedirect(route('username.welcome'));

    $user = User::where('email', 'octo@example.com')->first();

    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar_path)->toStartWith('avatars/');
});
