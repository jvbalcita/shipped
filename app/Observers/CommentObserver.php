<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\NotificationRecorder;

class CommentObserver
{
    public function __construct(private NotificationRecorder $notifications) {}

    public function created(Comment $comment): void
    {
        if ($comment->parent_id !== null) {
            // A reply notifies the parent comment's author.
            $parent = $comment->parent;

            if ($parent !== null && $parent->user_id !== null) {
                $this->notifications->record(
                    $parent->user,
                    'reply',
                    $comment->user,
                    $comment->project,
                    ['comment_id' => $comment->id],
                );
            }

            return;
        }

        // A top-level comment notifies the project owner.
        $this->notifications->record(
            $comment->project->creator,
            'comment',
            $comment->user,
            $comment->project,
            ['comment_id' => $comment->id],
        );
    }
}
