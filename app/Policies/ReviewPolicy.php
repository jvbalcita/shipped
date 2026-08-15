<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only the author may edit their review.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->is($review->user);
    }

    /**
     * Only the author may delete their review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->is($review->user);
    }
}
