<?php

namespace App\Concerns;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Models that can be followed (users, projects). Mirrors the polymorphic
 * Cheers pattern: one relationship plus small helpers so every followable
 * shares one consistent store/remove behavior.
 */
trait Followable
{
    /** @return MorphMany<Follow, $this> */
    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('user_id', $user->id)->exists();
    }

    public function addFollower(User $user): void
    {
        // Race-safe via the unique (user_id, followable_type, followable_id) index.
        $this->followers()->firstOrCreate(['user_id' => $user->id]);
    }

    public function removeFollower(User $user): void
    {
        $this->followers()->where('user_id', $user->id)->delete();
    }
}
