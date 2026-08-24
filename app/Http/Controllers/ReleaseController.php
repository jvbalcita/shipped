<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\Seo\SeoMetadata;
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

        $release->load(['project.creator', 'project.category']);
        $routeParameters = [
            'creator' => $creator,
            'project' => $project,
            'release' => $release,
        ];
        $canonicalUrl = route('releases.show', $routeParameters);
        $seo = new SeoMetadata(
            title: $release->title.' — '.$project->name.' — Shipped',
            description: SeoMetadata::summary(
                $release->notes,
                $project->tagline ?? 'A published release from '.$project->name.' on Shipped.',
            ),
            canonicalUrl: $canonicalUrl,
            image: route('og.release', $routeParameters),
            imageAlt: 'Share Card for '.$release->title.' from '.$project->name,
            jsonLd: [SeoMetadata::breadcrumbList([
                ['name' => 'Home', 'url' => route('home')],
                ['name' => $creator->name, 'url' => route('creators.show', $creator)],
                ['name' => $project->name, 'url' => route('projects.show', [
                    'creator' => $creator,
                    'project' => $project,
                ])],
                ['name' => $release->title, 'url' => $canonicalUrl],
            ])],
        );

        return Inertia::render('Releases/Show', [
            'release' => $release,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }
}
