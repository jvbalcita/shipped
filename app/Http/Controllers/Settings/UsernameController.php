<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UsernameUpdateRequest;
use App\Models\ReservedUsername;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class UsernameController extends Controller
{
    /**
     * Update the user's username, reserving the previous value for squat protection.
     */
    public function update(UsernameUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $newUsername = $request->input('username');

        $key = 'username-change:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()
                ->withErrors(['username' => __('You can only change your username once per cooldown period.')])
                ->withInput();
        }

        if ($user->username === $newUsername) {
            return back()
                ->withErrors(['username' => __('Choose a username different from your current one.')])
                ->withInput();
        }

        ReservedUsername::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'expires_at' => now()->addDays((int) config('shipped.username_reservation_days', 30)),
        ]);

        $user->fill(['username' => $newUsername])->save();

        RateLimiter::hit($key, (int) config('shipped.username_change_cooldown_minutes', 10080) * 60);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Username updated.')]);

        return to_route('profile.edit');
    }
}
