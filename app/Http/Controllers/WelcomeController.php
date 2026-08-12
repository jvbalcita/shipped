<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
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

        return Inertia::render('Welcome', [
            ...$stats,
            'ogTitle' => 'Shipped — A public home for launches',
            'ogDescription' => 'A public registry for independent launches worth sharing.',
        ]);
    }
}
