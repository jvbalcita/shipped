<?php

namespace App\Services\Discovery;

use App\Models\Cheer;
use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginates the project-card listing shared by every public discovery
 * surface — Discover, the per-technology Built With pages, and curated
 * Collections — so all of them present identical cards.
 */
class DiscoverProjects
{
    /**
     * @param  array{q?: string|null, category?: string|null, pricing?: string|null, technologies?: array<int, string>|null, sort?: string|null}  $filters
     * @param  Closure(Builder<Project>): Builder<Project>|null  $scope
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters = [], ?User $viewer = null, ?Closure $scope = null): LengthAwarePaginator
    {
        $viewerCheeredProjectIds = $viewer !== null
            ? Cheer::query()
                ->where('user_id', $viewer->id)
                ->where('cheerable_type', 'project')
                ->pluck('cheerable_id')
                ->all()
            : [];

        $sort = $filters['sort'] ?? 'latest';

        /** @var LengthAwarePaginator<int, array<string, mixed>> */
        $paginator = Project::query()
            ->discoverable()
            ->when($scope instanceof Closure, fn (Builder $query): Builder => $scope($query))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($query) => $query->where('slug', $slug)))
            ->when($filters['pricing'] ?? null, fn ($query, $pricing) => $query->where('pricing', $pricing))
            ->when($filters['technologies'] ?? null, function ($query, array $slugs): void {
                foreach ($slugs as $slug) {
                    $query->whereHas('technologies', fn ($query) => $query->where('slug', $slug));
                }
            })
            ->with([
                'creator:id,name,username',
                'category:id,name,slug',
                'tags:id,name,slug',
                'technologies:id,name,slug',
                'shipStory',
            ])
            ->withCount('cheers')
            ->withAvg('reviews', 'rating')
            ->when(
                $sort === 'cheered',
                fn ($query) => $query->orderByDesc('cheers_count')->latest(),
                fn ($query) => match (true) {
                    $sort === 'launch_date' => $query->orderByDesc('launch_date')->latest(),
                    // The scope owns the order (e.g. a collection's
                    // hand-picked positions); applying nothing preserves it.
                    $sort === 'curated' => $query,
                    default => $query->latest(),
                },
            )
            ->paginate(9)
            ->through(fn (Project $project): array => [
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
                'cheered_by_viewer' => in_array($project->id, $viewerCheeredProjectIds, true),
                'rating_average' => $project->reviews_avg_rating !== null
                    ? round((float) $project->reviews_avg_rating, 1)
                    : null,
                'creator' => $project->creator?->only('id', 'name', 'username'),
                'category' => $project->category?->only('id', 'name', 'slug'),
                'tags' => $project->tags->map->only('id', 'name', 'slug')->values(),
                'technologies' => $project->technologies->map->only('id', 'name', 'slug')->take(4)->values(),
                'ship_story_excerpt' => $project->shipStory?->excerpt(),
            ])
            ->withQueryString();

        return $paginator;
    }
}
