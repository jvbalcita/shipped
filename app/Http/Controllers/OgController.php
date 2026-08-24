<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class OgController extends Controller
{
    public function site(): Response
    {
        return response()
            ->view('og.site')
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Render a Creator Shipping Profile share card as a self-contained SVG.
     */
    public function creator(User $creator): Response
    {
        /** @var Collection<int, Project> $projects */
        $projects = $creator->projects()
            ->discoverable()
            ->withCount([
                'releases as published_releases_count' => fn ($query) => $query->published(),
            ])
            ->get();

        return response()
            ->view('og.creator', [
                'creator' => $creator,
                'projectCount' => $projects->count(),
                'verifiedProjectCount' => $projects
                    ->where('verification_status', 'verified')
                    ->count(),
                'releaseCount' => $projects->sum(
                    fn (Project $project): int => (int) $project->published_releases_count,
                ),
            ])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }

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
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
    }

    public function release(User $creator, Project $project, Release $release): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless($release->project->is($project), 404);
        abort_unless(Project::query()->discoverable()->whereKey($project)->exists(), 404);
        abort_unless($release->published_at?->isPast(), 404);

        return response()
            ->view('og.release', [
                'release' => $release->load(['project.creator']),
            ])
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=300');
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
