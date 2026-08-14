<?php

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
    Socialite::fake('github');

    $response = $this->get(route('oauth.redirect', ['provider' => 'github']));

    $response->assertRedirectContains('socialite.fake/github');
});

test('unknown provider returns 404 on redirect', function () {
    $this->get(route('oauth.redirect', ['provider' => 'facebook']))
        ->assertNotFound();
});

test('new oauth user is created and logged in', function () {
    Socialite::fake('github', oauthFakeUser());

    $response = $this->get(route('oauth.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = Auth::user();
    expect($user->email)->toBe('octo@example.com');
    expect(OAuthAccount::where('provider', 'github')->where('provider_id', 'provider-id-123')->exists())->toBeTrue();
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
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'octo@example.com')->first();

    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar_path)->toStartWith('avatars/');
});
