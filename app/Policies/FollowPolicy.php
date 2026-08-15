<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class FollowPolicy
{
    /**
     * Any authenticated member may follow a creator or project, except
     * themselves — no self-follow. Following your own project is allowed.
     */
    public function follow(User $user, User|Project $followable): bool
    {
        if ($followable instanceof User) {
            return ! $followable->is($user);
        }

        return true;
    }
}
