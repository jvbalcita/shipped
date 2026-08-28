<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Release;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [
            route('home'),
            route('discover'),
            route('technologies.index'),
            route('collections.index'),
        ];

        $collections = Collection::query()
            ->withLiveMembers()
            ->orderBy('id')
            ->get(['id', 'slug']);

        foreach ($collections as $collection) {
            $urls[] = route('collections.show', $collection);
        }

        $creators = User::query()
            ->whereIn('id', Project::query()->discoverable()->select('user_id'))
            ->orderBy('id')
            ->get(['id', 'username']);

        foreach ($creators as $creator) {
            $urls[] = route('creators.show', $creator);
        }

        $projects = Project::query()
            ->discoverable()
            ->with('creator:id,username')
            ->orderBy('id')
            ->get();

        foreach ($projects as $project) {
            $urls[] = route('projects.show', [
                'creator' => $project->creator,
                'project' => $project,
            ]);
        }

        $releases = Release::query()
            ->published()
            ->whereIn('project_id', Project::query()->discoverable()->select('id'))
            ->with('project.creator')
            ->orderBy('id')
            ->get();

        foreach ($releases as $release) {
            $urls[] = route('releases.show', [
                'creator' => $release->project->creator,
                'project' => $release->project,
                'release' => $release,
            ]);
        }

        $technologies = Technology::query()
            ->whereIn('id', ProjectTechnology::query()
                ->select('technology_id')
                ->whereIn('project_id', Project::query()->discoverable()->select('id')))
            ->orderBy('id')
            ->get(['id', 'slug']);

        foreach ($technologies as $technology) {
            $urls[] = route('technologies.show', $technology);
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300, must-revalidate');
    }

    public function robots(): Response
    {
        return response()
            ->view('robots', ['sitemapUrl' => route('sitemap')])
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300, must-revalidate');
    }
}
