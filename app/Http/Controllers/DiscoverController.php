<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPricing;
use App\Models\Category;
use App\Models\Technology;
use App\Services\Discovery\DiscoverProjects;
use App\Services\Seo\SeoMetadata;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscoverController extends Controller
{
    public function __construct(private readonly DiscoverProjects $discoverProjects) {}

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
            'technologies' => ['nullable', 'array', 'max:6'],
            'technologies.*' => ['string', 'exists:technologies,slug'],
            'sort' => ['nullable', 'in:latest,cheered,launch_date'],
        ]);

        $sort = $filters['sort'] ?? 'latest';
        $projects = $this->discoverProjects->paginate([...$filters, 'sort' => $sort], $request->user());

        $seo = new SeoMetadata(
            title: 'Discover verified Laravel projects — Shipped',
            description: 'Browse verified Laravel projects and the people who ship them.',
            canonicalUrl: route('discover'),
            robots: $request->query() === [] ? 'index,follow' : 'noindex,follow',
            image: route('og.site'),
            imageAlt: 'Shipped — The verified launch registry for Laravel projects',
        );

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
            'technologyOptions' => Technology::groupedVocabulary(),
            'activeTechnologies' => ($filters['technologies'] ?? [])
                ? Technology::query()->whereIn('slug', $filters['technologies'])->get(['id', 'name', 'slug'])->values()
                : [],
            'filters' => [...$filters, 'sort' => $sort],
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }
}
