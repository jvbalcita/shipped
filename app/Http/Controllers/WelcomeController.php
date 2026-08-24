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
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }
}
