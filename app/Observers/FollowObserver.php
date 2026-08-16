<?php

namespace App\Observers;

use App\Models\Follow;
use App\Models\User;
use App\Services\NotificationRecorder;

class FollowObserver
{
    public function __construct(private NotificationRecorder $notifications) {}

    public function created(Follow $follow): void
    {
        $followable = $follow->followable;

        // Only creator follows notify in v1 — the five triggers are all
        // recipient-targeted, and there is nobody to notify for a project
        // follow beyond the owner (who is not "followed").
        if ($followable instanceof User) {
            $this->notifications->record(
                $followable,
                'follow',
                $follow->user,
                $followable,
            );
        }
    }
}
