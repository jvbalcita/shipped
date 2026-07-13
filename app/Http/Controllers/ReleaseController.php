<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function show(User $creator, Project $project, Release $release): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless($release->project->is($project), 404);
        abort_unless($project->isPubliclyDiscoverable(), 404);
        abort_unless($release->published_at?->isPast(), 404);

        return Inertia::render('Releases/Show', [
            'release' => $release->load(['project.creator', 'project.category']),
        ]);
    }
}
