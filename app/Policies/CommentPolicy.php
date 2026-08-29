<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Only the author may edit their comment (within the 15-minute window).
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->is($comment->user);
    }

    /**
     * The author may always delete their comment; curators may remove any
     * comment while acting on a content report.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->is($comment->user) || $user->can('curate');
    }
}
