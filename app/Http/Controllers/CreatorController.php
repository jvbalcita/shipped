<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CreatorController extends Controller
{
    public function show(User $creator): Response
    {
        $creator->loadCount('followers');
        $viewer = request()->user();
        $projects = $creator->projects()
            ->discoverable()
            ->with([
                'creator:id,name,username',
                'category:id,name,slug',
                'tags:id,name,slug',
                'shipStory',
                'releases' => fn ($query) => $query->published()->latest('published_at'),
            ])
            ->withCount([
                'cheers',
                'releases as published_releases_count' => fn ($query) => $query->published(),
            ])
            ->withAvg('reviews', 'rating')
            ->get();
        $shippingHistory = $projects
            ->sortByDesc(fn (Project $project): int => $this->shippingTimestamp($project))
            ->values();
        $featuredProjects = $projects
            ->filter(fn (Project $project): bool => $project->profile_featured_order !== null)
            ->sortBy('profile_featured_order')
            ->take(3)
            ->values();

        return Inertia::render('Creators/Show', [
            'creator' => [
                ...$creator->only('id', 'name', 'username', 'title', 'location', 'bio', 'avatar_path', 'links'),
                'avatar_url' => $creator->avatar_path === null
                    ? null
                    : Storage::disk()->url($creator->avatar_path),
                'followers_count' => $creator->followers_count,
                'followed_by_viewer' => $viewer !== null && $creator->isFollowedBy($viewer),
                'stats' => [
                    'public_projects' => $projects->count(),
                    'verified_projects' => $projects
                        ->where('verification_status', 'verified')
                        ->count(),
                    'ship_stories' => $projects
                        ->filter(fn (Project $project): bool => $project->shipStory?->isApprovedAndComplete() === true)
                        ->count(),
                    'releases' => $projects->sum(
                        fn (Project $project): int => (int) $project->published_releases_count,
                    ),
                ],
            ],
            'profile_url' => route('creators.show', $creator),
            'featured_projects' => $featuredProjects
                ->map(fn (Project $project): array => $this->projectProps($project))
                ->values(),
            'shipping_history' => $shippingHistory
                ->map(fn (Project $project): array => $this->shippingHistoryProps($project))
                ->values(),
            'projects' => $projects
                ->map(fn (Project $project): array => $this->projectProps($project))
                ->values(),
            'ogTitle' => $creator->name.' — Shipping Profile — Shipped',
            'ogDescription' => $projects->count().' public launches filed by @'.$creator->username.'.',
            'ogImage' => route('og.creator', $creator),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectProps(Project $project): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingHistoryProps(Project $project): array
    {
        return [
            'project' => $this->projectProps($project),
            'latest_release' => $this->releaseProps($project->releases->first()),
            'release_count' => (int) $project->published_releases_count,
            'ship_story_excerpt' => $project->shipStory?->excerpt(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function releaseProps(?Release $release): ?array
    {
        if ($release === null) {
            return null;
        }

        return [
            'id' => $release->id,
            'title' => $release->title,
            'notes' => $release->notes,
            'published_at' => $release->published_at?->toIso8601String(),
        ];
    }

    private function shippingTimestamp(Project $project): int
    {
        $latestRelease = $project->releases->first();
        $date = ($latestRelease instanceof Release ? $latestRelease->published_at : null)
            ?? $project->launch_date
            ?? $project->filed_at
            ?? $project->created_at;

        return $date instanceof \DateTimeInterface
            ? $date->getTimestamp()
            : (int) strtotime((string) $date);
    }
}
