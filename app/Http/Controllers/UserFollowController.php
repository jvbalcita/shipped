<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserFollowController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        $this->authorize('follow', $user);

        $user->addFollower($this->currentUser());

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('follow', $user);

        $user->removeFollower($this->currentUser());

        return back();
    }
}
