<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Response;

class ManifestController extends Controller
{
    /**
     * Render the collectible launch manifest as a self-contained SVG.
     * Only discoverable (publicly filed) launches get a manifest — same
     * privacy rule as the OG preview images.
     */
    public function show(User $creator, Project $project): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless(Project::query()->discoverable()->whereKey($project)->exists(), 404);

        $project->load(['creator', 'tags']);

        $firstCheer = $project->cheers()->oldest('created_at')->first();

        return response()
            ->view('manifests.project', [
                'project' => $project,
                'launchDate' => $project->launch_date ?? $project->filed_at ?? $project->created_at,
                'firstCheerUsername' => $firstCheer?->user?->username,
            ])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
