<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContentReportPolicy
{
    /**
     * A signed-in builder may report any visible registry content except
     * their own — reporting your own project, comment, or review carries no
     * moderation signal and only pollutes the queue.
     */
    public function create(User $user, Model $reportable): bool
    {
        $author = match (true) {
            $reportable instanceof Project => $reportable->creator,
            $reportable instanceof Comment => $reportable->user,
            $reportable instanceof Review => $reportable->user,
            default => null,
        };

        return $author === null || ! $user->is($author);
    }
}
