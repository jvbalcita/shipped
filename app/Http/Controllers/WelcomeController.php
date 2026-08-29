<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Services\Seo\SeoMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Render the landing page with a snapshot of the live registry.
     */
    public function __invoke(Request $request): Response
    {
        $stats = Cache::remember('shipped:registry:stats', now()->addMinute(), function () {
            $latestDispatchAt = Release::query()
                ->published()
                ->latest('published_at')
                ->value('published_at');

            return [
                'launchCount' => Project::query()->discoverable()->count(),
                'creatorCount' => Project::query()->discoverable()->distinct()->count('user_id'),
                'latestDispatchAt' => $latestDispatchAt?->toIso8601String(),
            ];
        });

        $recentLaunches = Cache::remember('shipped:registry:recent', now()->addMinute(), function () {
            return Project::query()
                ->discoverable()
                ->with([
                    'creator:id,name,username',
                    'category:id,name,slug',
                    'tags:id,name,slug',
                    'technologies:id,name,slug',
                    'shipStory',
                ])
                ->withCount('cheers')
                ->withAvg('reviews', 'rating')
                ->latest('filed_at')
                ->latest('id')
                ->take(6)
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'tagline' => $project->tagline,
                    'cover_image_url' => $project->cover_image_url,
                    'logo_url' => $project->logo_url,
                    'pricing' => $project->pricing?->value,
                    'pricing_label' => $project->pricing?->label(),
                    'launch_date' => $project->launch_date?->toDateString(),
                    'verification_status' => $project->verification_status,
                    'filed_serial' => $project->filed_serial,
                    'cheers_count' => $project->cheers_count,
                    'rating_average' => $project->reviews_avg_rating !== null
                        ? round((float) $project->reviews_avg_rating, 1)
                        : null,
                    'ship_story_excerpt' => $project->shipStory?->excerpt(),
                    'creator' => $project->creator?->only('id', 'name', 'username'),
                    'category' => $project->category?->only('id', 'name', 'slug'),
                    'tags' => $project->tags->map->only('id', 'name', 'slug')->values(),
                    'technologies' => $project->technologies->map->only('id', 'name', 'slug')->take(4)->values(),
                ])
                ->values()
                ->all();
        });

        $seo = new SeoMetadata(
            title: 'Shipped — The verified launch registry for Laravel projects',
            description: 'Discover verified Laravel projects and the people who ship them.',
            canonicalUrl: route('home'),
            image: route('og.site'),
            imageAlt: 'Shipped — The verified launch registry for Laravel projects',
            jsonLd: [[
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => route('home').'#website',
                'name' => 'Shipped',
                'url' => route('home'),
            ]],
        );

        return Inertia::render('Welcome', [
            ...$stats,
            'recentLaunches' => $recentLaunches,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }
}
