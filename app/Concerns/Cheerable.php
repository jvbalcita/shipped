<?php

namespace App\Concerns;

use App\Models\Cheer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Models that can be cheered (projects, comments, …). Centralizes the
 * polymorphic cheers relationship and an idempotent toggle so every cheerable
 * shares one consistent store/unstore behavior.
 */
trait Cheerable
{
    /** @return MorphMany<Cheer, $this> */
    public function cheers(): MorphMany
    {
        return $this->morphMany(Cheer::class, 'cheerable');
    }

    /**
     * Toggle the given user's cheer on this model: add it if absent, remove it
     * if present. Race-safe via the unique (user_id, cheerable_type, cheerable_id) index.
     */
    public function toggleCheer(User $user): void
    {
        $existing = $this->cheers()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            $existing->delete();

            return;
        }

        $this->cheers()->create(['user_id' => $user->id]);
    }
}
