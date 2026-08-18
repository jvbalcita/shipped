<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\UsernameClaimRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsernameController extends Controller
{
    /**
     * Show the handle-picker step offered after provider sign-up.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->username_claimed_at !== null) {
            return redirect()->route('profile.edit');
        }

        return Inertia::render('auth/ClaimUsername', [
            'username' => $user->username,
        ]);
    }

    /**
     * Claim the chosen handle for the first time.
     *
     * First picks skip the change-cooldown and the old-name reservation:
     * the previous handle was auto-generated, never chosen by the creator.
     */
    public function update(UsernameClaimRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->username_claimed_at !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Your username is already claimed.')]);

            return redirect()->route('profile.edit');
        }

        $user->fill([
            'username_claimed_at' => now(),
        ]);

        if ($user->username !== $request->validated('username')) {
            $user->fill(['username' => $request->validated('username')]);
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Username claimed.')]);

        return redirect()->route('dashboard');
    }
}
