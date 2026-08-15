<?php

namespace App\Observers;

use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Project;
use App\Services\ActivityRecorder;

class CheerObserver
{
    public function __construct(private ActivityRecorder $activities) {}

    public function created(Cheer $cheer): void
    {
        $cheerable = $cheer->cheerable;

        if ($cheerable instanceof Project) {
            $this->activities->record('cheered', $cheer->user, $cheerable, $cheer->created_at, [
                'cheerable' => 'project',
            ]);

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
