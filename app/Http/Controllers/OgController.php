<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Response;

class OgController extends Controller
{
    /**
     * Render a per-launch social-preview card as a self-contained SVG.
     * Only discoverable (publicly filed) launches get a preview image.
     */
    public function project(User $creator, Project $project): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless(Project::query()->discoverable()->whereKey($project)->exists(), 404);

        return response()
            ->view('og.project', [
                'project' => $project->load(['creator', 'category', 'shipStory']),
            ])
            ->header('Content-Type', 'image/svg+xml');
    }

    /**
     * Render a typographic launch plate for a project with no uploaded cover.
     * Available to the same audience that can view the project record.
     */
    public function cover(User $creator, Project $project): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless(request()->user()?->is($project->creator) || Project::query()->discoverable()->whereKey($project)->exists(), 404);

        return response()
            ->view('cover.project', [
                'project' => $project->load(['creator']),
            ])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
