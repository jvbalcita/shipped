<?php

namespace App\Observers;

use App\Models\Release;
use App\Services\ActivityRecorder;

class ReleaseObserver
{
    public function __construct(private ActivityRecorder $activities) {}

    public function saved(Release $release): void
    {
        // Only a release that actually publishes feeds an event; scheduled
        // releases carry a future occurred_at and surface once due.
        if ($release->published_at === null) {
            return;
        }

        $project = $release->project;
        $creator = $project->creator;

        $this->activities->record('released', $creator, $project, $release->published_at, [
            'release_id' => $release->id,
            'release_title' => $release->title,
        ]);

        // The scheduled publisher flips is_public without touching the
        // release, so also catch a first public launch here.
        if ($project->is_public && $project->verification_status === 'verified') {
            $this->activities->recordOnce('launched', $creator, $project, $release->published_at);
        }
    }
}
