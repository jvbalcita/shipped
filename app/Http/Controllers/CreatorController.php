<?php

namespace App\Http\Controllers;

use App\Models\Project;
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

        return Inertia::render('Creators/Show', [
            'creator' => [
                ...$creator->only('name', 'username', 'title', 'location', 'bio', 'avatar_path', 'links'),
                'avatar_url' => $creator->avatar_path === null
                    ? null
                    : Storage::disk()->url($creator->avatar_path),
                'followers_count' => $creator->followers_count,
                'followed_by_viewer' => $viewer !== null && $creator->isFollowedBy($viewer),
            ],
            'projects' => $creator->projects()
                ->discoverable()
                ->with(['creator', 'category', 'tags', 'shipStory'])
                ->withCount('cheers')
                ->latest()
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
                    'ship_story_excerpt' => $project->shipStory?->excerpt(),
                    'creator' => $project->creator?->only('id', 'name', 'username'),
                    'category' => $project->category?->only('id', 'name', 'slug'),
                    'tags' => $project->tags->map->only('id', 'name', 'slug')->values(),
                ])
                ->values(),
            'ogTitle' => $creator->name.' — Shipped',
            'ogDescription' => 'Public launches filed by @'.$creator->username.'.',
        ]);
    }
}
