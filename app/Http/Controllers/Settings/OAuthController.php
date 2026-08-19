<?php

namespace App\Http\Controllers\Settings;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class OAuthController extends Controller
{
    /**
     * Begin linking a provider to the authenticated creator.
     */
    public function link(Request $request, string $provider): Response
    {
        $providerEnum = OAuthProvider::tryFrom($provider);

        if ($providerEnum === null) {
            abort(404);
        }

        $request->session()->put('oauth_link_intent', true);

        // An XHR cannot follow the cross-origin redirect to the provider, so
        // hand the authorize URL back to Inertia for a full-page navigation.
        return Inertia::location(Socialite::driver($providerEnum->value)->redirect());
    }

    /**
     * Unlink a provider from the authenticated creator.
     */
    public function unlink(Request $request, string $provider): RedirectResponse
    {
        $providerEnum = OAuthProvider::tryFrom($provider);

        if ($providerEnum === null) {
            abort(404);
        }

        $user = $request->user();

        $hasAlternativeSignIn = $user->password !== null
            || $user->passkeys()->exists()
            || $user->oauthAccounts()->where('provider', '!=', $providerEnum->value)->exists();

        if (! $hasAlternativeSignIn) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Set a password before removing your last sign-in method.')]);

            return Redirect::route('security.edit');
        }

        $user->oauthAccounts()
            ->where('provider', $providerEnum->value)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Provider unlinked.')]);

        return Redirect::route('security.edit');
    }
}
