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
     * The author may delete their review; curators may remove any review
     * while acting on a content report.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->is($review->user) || $user->can('curate');
    }
}
