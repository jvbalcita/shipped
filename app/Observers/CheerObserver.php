<?php

namespace App\Observers;

use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Project;
use App\Services\ActivityRecorder;
use App\Services\NotificationRecorder;

class CheerObserver
{
    public function __construct(
        private ActivityRecorder $activities,
        private NotificationRecorder $notifications,
    ) {}

    public function created(Cheer $cheer): void
    {
        $cheerable = $cheer->cheerable;

        if ($cheerable instanceof Project) {
            $this->activities->record('cheered', $cheer->user, $cheerable, $cheer->created_at, [
                'cheerable' => 'project',
            ]);

            $this->notifications->record(
                $cheerable->creator,
                'cheer',
                $cheer->user,
                $cheerable,
            );

            return;
        }

        if ($cheerable instanceof Comment) {
            // Comment cheers surface the commented project in the feed.
            $this->activities->record('cheered', $cheer->user, $cheerable->project, $cheer->created_at, [
                'cheerable' => 'comment',
                'comment_id' => $cheerable->id,
            ]);
        }
    }
}
