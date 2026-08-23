<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPricing;
use App\Models\Category;
use App\Models\Cheer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscoverController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->filled('q') && $request->filled('search')) {
            $search = $request->query('search');

            if (is_string($search)) {
                $request->merge(['q' => $search]);
            }
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'pricing' => ['nullable', 'string', Rule::enum(ProjectPricing::class)],
            'sort' => ['nullable', 'in:latest,cheered,launch_date'],
        ]);

        $sort = $filters['sort'] ?? 'latest';

        $viewer = $request->user();
        $viewerCheeredProjectIds = $viewer !== null
            ? Cheer::query()
                ->where('user_id', $viewer->id)
                ->where('cheerable_type', 'project')
                ->pluck('cheerable_id')
                ->all()
            : [];

        $projects = Project::query()
            ->discoverable()
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($query) => $query->where('slug', $slug)))
            ->when($filters['pricing'] ?? null, fn ($query, $pricing) => $query->where('pricing', $pricing))
            ->with([
                'creator:id,name,username',
                'category:id,name,slug',
                'tags:id,name,slug',
                'shipStory',
            ])
            ->withCount('cheers')
            ->withAvg('reviews', 'rating')
            ->when(
                $sort === 'cheered',
                fn ($query) => $query->orderByDesc('cheers_count')->latest(),
                fn ($query) => $sort === 'launch_date'
                    ? $query->orderByDesc('launch_date')->latest()
                    : $query->latest(),
            )
            ->paginate(9)
            ->through(fn (Project $project) => [
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
                'ship_story_excerpt' => $project->shipStory?->excerpt(),
            ])
            ->withQueryString();

        return Inertia::render('Discover/Index', [
            'projects' => $projects,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'pricingOptions' => collect(ProjectPricing::cases())->map(fn (ProjectPricing $pricing) => [
                'value' => $pricing->value,
                'label' => $pricing->label(),
            ])->values(),
            'activeCategory' => $filters['category'] ?? null
                ? Category::query()->where('slug', $filters['category'])->first(['id', 'name', 'slug'])
                : null,
            'filters' => [...$filters, 'sort' => $sort],
        ]);
    }
}
