<?php

namespace App\Http\Controllers;

use App\Enums\TechnologyGroup;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Technology;
use App\Services\Discovery\DiscoverProjects;
use App\Services\Seo\SeoMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    public function __construct(private readonly DiscoverProjects $discoverProjects) {}

    /**
     * The Built With index: the curated stack vocabulary with the number
     * of discoverable projects behind each technology.
     */
    public function index(): Response
    {
        $counts = ProjectTechnology::query()
            ->whereIn('project_id', Project::query()->discoverable()->select('id'))
            ->groupBy('technology_id')
            ->selectRaw('technology_id, count(*) as aggregate')
            ->pluck('aggregate', 'technology_id');

        $groups = collect(TechnologyGroup::cases())
            ->map(fn (TechnologyGroup $group): array => [
                'group' => $group->value,
                'label' => $group->label(),
                'technologies' => Technology::query()
                    ->where('stack_group', $group->value)
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Technology $technology): array => [
                        'id' => $technology->id,
                        'name' => $technology->name,
                        'slug' => $technology->slug,
                        'projects_count' => (int) ($counts[$technology->id] ?? 0),
                    ])
                    ->sortByDesc('projects_count')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $seo = new SeoMetadata(
            title: 'Built With — browse Laravel projects by stack — Shipped',
            description: 'Browse verified Laravel projects by the technologies they are built with: Laravel version, frontend, database, infrastructure, and packages.',
            canonicalUrl: route('technologies.index'),
            image: route('og.site'),
            imageAlt: 'Shipped — The verified launch registry for Laravel projects',
        );

        return Inertia::render('Technologies/Index', [
            'groups' => $groups,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }

    /**
     * One technology's page: every discoverable project that declares it.
     */
    public function show(Request $request, Technology $technology): Response
    {
        $projects = $this->discoverProjects->paginate(
            ['sort' => 'latest'],
            $request->user(),
            fn (Builder $query): Builder => $query->whereHas(
                'technologies',
                fn (Builder $query): Builder => $query->where('slug', $technology->slug),
            ),
        );

        $canonicalUrl = route('technologies.show', $technology);

        $seo = new SeoMetadata(
            title: "Built with {$technology->name} — Shipped",
            description: "Browse verified Laravel projects built with {$technology->name}, with the stories and stacks behind them.",
            canonicalUrl: $canonicalUrl,
            // Thin pages stay out of the index until real projects back them.
            robots: $projects->total() > 0 ? 'index,follow' : 'noindex,follow',
            image: route('og.site'),
            imageAlt: "Laravel projects built with {$technology->name} — Shipped",
            jsonLd: [
                SeoMetadata::breadcrumbList([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Built With', 'url' => route('technologies.index')],
                    ['name' => $technology->name, 'url' => $canonicalUrl],
                ]),
            ],
        );

        return Inertia::render('Technologies/Show', [
            'technology' => [
                'name' => $technology->name,
                'slug' => $technology->slug,
                'group_label' => $technology->stack_group->label(),
            ],
            'projects' => $projects,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }
}
