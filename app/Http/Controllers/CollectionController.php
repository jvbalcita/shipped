<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\SaveCollectionRequest;
use App\Models\Collection;
use App\Models\Project;
use App\Services\Discovery\DiscoverProjects;
use App\Services\ProductEventRecorder;
use App\Services\Seo\SeoMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $productEvents) {}

    /**
     * The Collections index: curator-edited entry points into the
     * verified registry. Collections without live members stay hidden
     * so the registry never advertises an empty page.
     */
    public function index(): Response
    {
        $collections = Collection::query()
            ->withLiveMembers()
            ->withCount(['discoverableProjects as projects_count'])
            ->latest()
            ->get(['id', 'title', 'slug', 'description', 'cover_image_path', 'updated_at'])
            ->map(fn (Collection $collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'slug' => $collection->slug,
                'description' => SeoMetadata::summary($collection->description, '', 200),
                'cover_image_url' => $collection->cover_image_url,
                'projects_count' => $collection->projects_count,
            ])
            ->values()
            ->all();

        $seo = new SeoMetadata(
            title: 'Collections — curated picks from the verified registry — Shipped',
            description: 'Hand-picked sets of verified Laravel projects, curated by what was actually shipped: the stack, the story, and the evidence behind each launch.',
            canonicalUrl: route('collections.index'),
            image: route('og.site'),
            imageAlt: 'Shipped — curated collections of verified Laravel projects',
        );

        return Inertia::render('Collections/Index', [
            'collections' => $collections,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }

    /**
     * One curated collection: the curator's narrative followed by its
     * member projects in hand-picked order. Suppressed members simply
     * do not appear while they are not discoverable.
     */
    public function show(Request $request, Collection $collection): Response
    {
        $this->productEvents->record(
            ProductEventName::CollectionPageViewed,
            $request->user(),
            $collection,
        );

        $projects = app(DiscoverProjects::class)->paginate(
            ['sort' => 'curated'],
            $request->user(),
            fn (Builder $query): Builder => $query
                ->join('collection_project', 'collection_project.project_id', '=', 'projects.id')
                ->where('collection_project.collection_id', $collection->id)
                ->orderBy('collection_project.position')
                ->select('projects.*'),
        );

        $canonicalUrl = route('collections.show', $collection);

        $seo = new SeoMetadata(
            title: "{$collection->title} — Shipped",
            description: SeoMetadata::summary(
                $collection->description,
                'A curated set of verified Laravel projects on Shipped.',
            ),
            canonicalUrl: $canonicalUrl,
            // Thin pages stay out of the index until projects back them.
            robots: $projects->total() > 0 ? 'index,follow' : 'noindex,follow',
            image: $collection->cover_image_url ?? route('og.site'),
            imageAlt: "{$collection->title} — curated Laravel projects on Shipped",
            jsonLd: [
                SeoMetadata::breadcrumbList([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Collections', 'url' => route('collections.index')],
                    ['name' => $collection->title, 'url' => $canonicalUrl],
                ]),
                $this->itemListJsonLd($collection),
            ],
        );

        return Inertia::render('Collections/Show', [
            'collection' => [
                'id' => $collection->id,
                'title' => $collection->title,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'cover_image_url' => $collection->cover_image_url,
                'updated_at' => $collection->updated_at->toIso8601String(),
            ],
            'projects' => $projects,
            'seo' => $seo->toArray(),
            ...$seo->legacyProps(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Collections/Create', [
            'candidates' => $this->candidates(),
        ]);
    }

    public function store(SaveCollectionRequest $request): RedirectResponse
    {
        $collection = DB::transaction(function () use ($request): Collection {
            $payload = $request->validatedPayload();

            $collection = Collection::query()->create([
                'title' => $payload['title'],
                'slug' => Collection::uniqueSlugFor($payload['title']),
                'description' => $payload['description'],
            ]);

            if ($request->hasFile('cover_image')) {
                $collection->forceFill([
                    'cover_image_path' => $request->file('cover_image')->store('collection-covers'),
                ])->save();
            }

            $this->syncMembers($collection, $payload['project_ids']);

            return $collection;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Collection created.',
        ]);

        return to_route('collections.show', $collection);
    }

    public function edit(Collection $collection): Response
    {
        $members = $collection->projects()
            ->with('creator:id,username')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'creator_username' => $project->creator?->username,
                'is_discoverable' => $project->isPubliclyDiscoverable(),
            ])
            ->values()
            ->all();

        return Inertia::render('Collections/Edit', [
            'collection' => [
                'id' => $collection->id,
                'title' => $collection->title,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'cover_image_url' => $collection->cover_image_url,
            ],
            'members' => $members,
            'candidates' => $this->candidates(),
        ]);
    }

    public function update(SaveCollectionRequest $request, Collection $collection): RedirectResponse
    {
        DB::transaction(function () use ($request, $collection): void {
            $payload = $request->validatedPayload();

            $collection->fill([
                'title' => $payload['title'],
                'slug' => Collection::uniqueSlugFor($payload['title'], $collection->id),
                'description' => $payload['description'],
            ]);

            if ($request->hasFile('cover_image')) {
                if ($collection->cover_image_path) {
                    Storage::delete($collection->cover_image_path);
                }

                $path = $request->file('cover_image')?->store('collection-covers');

                if (is_string($path)) {
                    $collection->cover_image_path = $path;
                }
            }

            $collection->save();

            $this->syncMembers($collection, $payload['project_ids']);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Collection updated.',
        ]);

        return to_route('collections.show', $collection);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        if ($collection->cover_image_path) {
            Storage::delete($collection->cover_image_path);
        }

        $collection->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Collection deleted.',
        ]);

        return to_route('collections.index');
    }

    /**
     * Replace the hand-picked membership with the submitted order.
     *
     * @param  list<int>  $projectIds
     */
    protected function syncMembers(Collection $collection, array $projectIds): void
    {
        $collection->projects()->detach();

        foreach ($projectIds as $position => $projectId) {
            $collection->projects()->attach($projectId, ['position' => $position + 1]);
        }
    }

    /**
     * @return list<array{id: int, name: string, creator_username: string|null}>
     */
    protected function candidates(): array
    {
        $candidates = Project::query()
            ->discoverable()
            ->with('creator:id,username')
            ->orderBy('name')
            ->get(['id', 'name', 'user_id'])
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'creator_username' => $project->creator?->username,
            ])
            ->values()
            ->all();

        /** @var list<array{id: int, name: string, creator_username: string|null}> $candidates */
        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    protected function itemListJsonLd(Collection $collection): array
    {
        $items = $collection->discoverableProjects()
            ->with('creator:id,username')
            ->get()
            ->values()
            ->map(fn (Project $project, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $project->name,
                'url' => route('projects.show', [
                    'creator' => $project->creator,
                    'project' => $project,
                ]),
            ])
            ->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $collection->title,
            'description' => SeoMetadata::summary($collection->description, '', 300),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items,
            ],
        ];
    }
}
