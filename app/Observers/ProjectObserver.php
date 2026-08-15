<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityRecorder;

class ProjectObserver
{
    public function __construct(private ActivityRecorder $activities) {}

    public function updated(Project $project): void
    {
        $becameVerified = $project->wasChanged('verification_status')
            && $project->verification_status === 'verified';

        if ($becameVerified) {
            $this->activities->record('verified', $project->creator, $project, $project->verified_at);
        }

        // "launched" fires the first time a project is publicly
        // discoverable (verified + public + published release).
        if (($becameVerified || ($project->wasChanged('is_public') && $project->is_public))
            && $project->isPubliclyDiscoverable()) {
            $this->activities->recordOnce('launched', $project->creator, $project, $project->verified_at ?? now());
        }
    }
}
