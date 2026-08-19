<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class OAuthController extends Controller
{
    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        $providerEnum = $this->resolveProvider($provider);

        if ($providerEnum === null || ! $providerEnum->isConfigured()) {
            abort(404);
        }

        return Socialite::driver($providerEnum->value)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $providerEnum = $this->resolveProvider($provider);

        if ($providerEnum === null) {
            abort(404);
        }

        try {
            /** @var \Laravel\Socialite\Two\User $socialiteUser */
            $socialiteUser = Socialite::driver($providerEnum->value)->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')
                ->withErrors(['email' => __('The social login was cancelled or invalid. Please try again.')]);
        }

        if ($request->session()->pull('oauth_link_intent') && Auth::check()) {
            $user = $request->user();

            $conflicting = OAuthAccount::where('provider', $providerEnum->value)
                ->where('provider_id', $socialiteUser->getId())
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($conflicting) {
                return redirect()->route('security.edit')
                    ->withErrors(['email' => __('This provider is already linked to another account.')]);
            }

            if (! $user->oauthAccounts()->where('provider', $providerEnum->value)->exists()) {
                $user->oauthAccounts()->create([
                    'provider' => $providerEnum->value,
                    'provider_id' => $socialiteUser->getId(),
                    'provider_token' => $socialiteUser->token,
                    'provider_refresh_token' => $socialiteUser->refreshToken,
                    'token_expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
                    'linked_at' => now(),
                ]);

                $this->importAvatarIfMissing($user, $socialiteUser->getAvatar());
            }

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Provider linked.')]);

            return redirect()->route('security.edit');
        }

        $account = OAuthAccount::where('provider', $providerEnum->value)
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        if ($account !== null) {
            Auth::login($account->user);
            $request->session()->put('auth.password_confirmed_at', time());

            return redirect()->intended(route('dashboard'));
        }

        $email = $socialiteUser->getEmail();

        if ($email !== null) {
            $existing = User::where('email', $email)->first();

            if ($existing !== null && ! $existing->oauthAccounts()->where('provider', $providerEnum->value)->exists()) {
                return redirect()->route('login')
                    ->withErrors(['email' => __('This email is already registered. Log in and link this provider from Settings > Security.')]);
            }
        }

        $user = User::create([
            'name' => $socialiteUser->getName() ?? $email ?? 'Creator',
            'username' => User::generateUniqueUsername($socialiteUser->getNickname() ?? $email ?? 'creator'),
            'email' => $email ?? $socialiteUser->getId().'@'.$providerEnum->value.'.shipped.local',
            'title' => 'Creator',
            'password' => null,
        ]);

        $user->email_verified_at = Carbon::now();
        $user->save();

        $user->oauthAccounts()->create([
            'provider' => $providerEnum->value,
            'provider_id' => $socialiteUser->getId(),
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken,
            'token_expires_at' => $socialiteUser->expiresIn ? now()->addSeconds($socialiteUser->expiresIn) : null,
            'linked_at' => now(),
        ]);

        $this->importAvatarIfMissing($user, $socialiteUser->getAvatar());

        Auth::login($user);
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->route('username.welcome');
    }

    private function importAvatarIfMissing(User $user, ?string $avatarUrl): void
    {
        if ($avatarUrl === null || $user->avatar_path !== null) {
            return;
        }

        $response = Http::timeout(5)->get($avatarUrl);

        if (! $response->successful()) {
            return;
        }

        $contentType = $response->header('Content-Type');

        if (! str_starts_with((string) $contentType, 'image/') || $response->body() === '') {
            return;
        }

        if (strlen($response->body()) > 3 * 1024 * 1024) {
            return;
        }

        $extension = match ($contentType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = 'avatars/'.Str::random(40).'.'.$extension;

        if (Storage::disk()->put($path, $response->body())) {
            $user->update(['avatar_path' => $path]);
        }
    }

    private function resolveProvider(string $provider): ?OAuthProvider
    {
        return OAuthProvider::tryFrom($provider);
    }
}
