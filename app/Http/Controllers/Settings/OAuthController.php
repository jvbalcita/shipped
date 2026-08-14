<?php

namespace App\Http\Controllers\Settings;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Begin linking a provider to the authenticated creator.
     */
    public function link(Request $request, string $provider): RedirectResponse
    {
        $providerEnum = OAuthProvider::tryFrom($provider);

        if ($providerEnum === null) {
            abort(404);
        }

        $request->session()->put('oauth_link_intent', true);

        return Socialite::driver($providerEnum->value)->redirect();
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

        $user->oauthAccounts()
            ->where('provider', $providerEnum->value)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Provider unlinked.')]);

        return Redirect::route('security.edit');
    }
}
