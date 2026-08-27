<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPricing;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Category;
use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectScreenshot;
use App\Models\Review;
use App\Models\ShipStory;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use App\Services\GitHub\GitHubClient;
use App\Services\HtmlSanitizer;
use App\Services\LaravelCloud\ProjectVerificationService;
use App\Services\Seo\SeoMetadata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\DeferProp;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(private readonly GitHubClient $github) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $creator = $this->currentUser();
        $connection = $creator->cloudConnection()
            ->with('connectedEnvironments')
            ->first();
        $environments = $connection === null
            ? collect()
            : $connection->connectedEnvironments;

        return Inertia::render('Projects/Index', [
            'projects' => $creator->projects()
                ->with('category:id,name')
                ->withCount(['cheers', 'releases'])
                ->latest()
                ->get(),
            'cloudConnection' => $connection === null ? null : [
                'status' => $connection->status,
                'last_validated_at' => $connection->last_validated_at,
                'environment_count' => $environments->count(),
            ],
            'connectedEnvironments' => $environments->map(fn ($environment) => [
                'id' => $environment->id,
                'application_id' => $environment->application_id,
                'application_name' => $environment->application_name,
                'environment_id' => $environment->environment_id,
                'environment_name' => $environment->environment_name,
                'domains' => $environment->domains,
                'synced_at' => $environment->synced_at,
            ])->values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'categories' => Category::query()->orderBy('name')->get(),
            'pricingOptions' => collect(ProjectPricing::cases())->map(fn (ProjectPricing $pricing) => [
                'value' => $pricing->value,
                'label' => $pricing->label(),
            ])->values(),
            'suggestedTags' => config('shipped.suggested_tags', []),
            'technologyOptions' => Technology::groupedVocabulary(),
            ...$this->githubRepositoryProps($this->currentUser()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $data = $request->safe()->except(['cover_image', 'logo', 'tags', 'technologies', 'screenshots', 'screenshots_captions']);
            if ($request->hasFile('cover_image')) {
                $data['cover_image_path'] = $request->file('cover_image')->store('project-covers');
            }
            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('project-logos');
            }
            $data['pricing'] = $data['pricing'] ?? ProjectPricing::Free->value;
            $data['slug'] = $this->uniqueSlug($data['name']);
            $project = $request->user()->projects()->create($data);
            $project->shipStory()->create();
            $this->syncTags($project, $request->string('tags')->toString());
            $this->syncTechnologies($project, $request->input('technologies', []));
            $this->storeScreenshots($project, $request);

            return $project;
        });

        return to_route('projects.edit', $project);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $creator, Project $project): Response
    {
        abort_unless($project->creator->is($creator), 404);
        $isDiscoverable = Project::query()->discoverable()->whereKey($project)->exists();
        abort_unless(request()->user()?->is($project->creator) || $isDiscoverable, 404);

        $project->load([
            'creator:id,name,username',
            'category:id,name,slug',
            'tags:id,name,slug',
            'technologies:id,name,slug,stack_group',
            'screenshots:id,project_id,path,caption,sort_order',
            'reviews.user:id,name,username',
            'releases' => fn ($query) => $query->published()->latest('published_at'),
            'shipStory',
        ])->loadCount(['cheers', 'reviews', 'followers'])
            ->loadAvg('reviews', 'rating');

        $seoProps = [];

        if ($isDiscoverable) {
            $canonicalUrl = route('projects.show', ['creator' => $creator, 'project' => $project]);
            $jsonLd = [
                SeoMetadata::breadcrumbList([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => $creator->name, 'url' => route('creators.show', $creator)],
                    ['name' => $project->name, 'url' => $canonicalUrl],
                ]),
            ];

            if ($project->live_url !== null) {
                $softwareApplication = [
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    '@id' => $canonicalUrl.'#software',
                    'name' => $project->name,
                    'description' => SeoMetadata::summary(
                        $project->tagline,
                        'A verified Laravel project shipped by @'.$creator->username.' on Shipped.',
                    ),
                    'url' => $project->live_url,
                ];
                if ($project->category?->name !== null) {
                    $softwareApplication['applicationCategory'] = $project->category->name;
                }
                if ($project->github_url !== null) {
                    $softwareApplication['sameAs'] = [$project->github_url];
                }

                $jsonLd[] = $softwareApplication;
            }

            $seo = new SeoMetadata(
                title: $project->name.' by @'.$creator->username.' — Shipped',
                description: SeoMetadata::summary(
                    $project->tagline,
                    'A verified Laravel project shipped by @'.$creator->username.' on Shipped.',
                ),
                canonicalUrl: $canonicalUrl,
                image: route('og.project', ['creator' => $creator, 'project' => $project]),
                imageAlt: 'Share Card for '.$project->name.' by @'.$creator->username,
                jsonLd: $jsonLd,
            );

            $seoProps = [
                'seo' => $seo->toArray(),
                ...$seo->legacyProps(),
            ];
        }

        $viewer = request()->user();
        $viewerReview = $viewer !== null
            ? $project->reviews->firstWhere('user_id', $viewer->id)
            : null;

        $project->load([
            'comments' => fn ($query) => $query
                ->with('user:id,name,username')
                ->withCount('cheers')
                ->oldest('created_at'),
        ]);

        $commentIds = $project->comments->modelKeys();
        $viewerCheeredCommentIds = $viewer !== null && $commentIds !== []
            ? Cheer::query()
                ->where('user_id', $viewer->id)
                ->where('cheerable_type', 'comment')
                ->whereIn('cheerable_id', $commentIds)
                ->pluck('cheerable_id')
                ->all()
            : [];

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'tagline' => $project->tagline,
                'description' => HtmlSanitizer::sanitize((string) $project->description),
                'cover_image_url' => $project->cover_image_url,
                'logo_url' => $project->logo_url,
                'live_url' => $project->live_url,
                'github_url' => $project->github_url,
                'pricing' => $project->pricing?->value,
                'pricing_label' => $project->pricing?->label(),
                'launch_date' => $project->launch_date?->toDateString(),
                'verification_status' => $project->verification_status,
                'filed_serial' => $project->filed_serial,
                'cheers_count' => $project->cheers_count,
                'followers_count' => $project->followers_count,
                'followed_by_viewer' => $viewer !== null && $project->isFollowedBy($viewer),
                'creator' => $project->creator->only('id', 'name', 'username'),
                'category' => $project->category?->only('id', 'name', 'slug'),
                'tags' => $project->tags->map->only('id', 'name', 'slug')->values(),
                'built_with' => $this->builtWithProps($project),
                'screenshots' => $project->screenshots->map(fn (ProjectScreenshot $screenshot) => [
                    'id' => $screenshot->id,
                    'url' => $screenshot->url,
                    'caption' => $screenshot->caption,
                ])->values(),
                'releases' => $project->releases->map(fn ($release) => $release->only(
                    'id',
                    'title',
                    'notes',
                    'published_at',
                ))->values(),
                'ship_story' => $isDiscoverable
                    ? $this->shipStoryProps($project->shipStory)
                    : null,
                'rating_average' => $project->reviews_avg_rating !== null
                    ? round((float) $project->reviews_avg_rating, 1)
                    : null,
                'reviews' => $project->reviews
                    ->map(fn (Review $review) => [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'body' => $review->body,
                        'created_at' => $review->created_at?->toIso8601String(),
                        'user' => $review->user?->only('id', 'name', 'username'),
                    ])
                    ->sortByDesc('created_at')
                    ->values(),
                'user_review' => $viewerReview !== null
                    ? ['id' => $viewerReview->id, 'rating' => $viewerReview->rating, 'body' => $viewerReview->body]
                    : null,
                'comments' => $project->comments->map(fn (Comment $comment) => [
                    'id' => $comment->id,
                    'parent_id' => $comment->parent_id,
                    'body' => $comment->deleted_at !== null ? null : $comment->body,
                    'is_deleted' => $comment->deleted_at !== null,
                    'created_at' => $comment->created_at?->toIso8601String(),
                    'can_edit' => $viewer !== null
                        && $viewer->is($comment->user)
                        && $comment->deleted_at === null
                        && $comment->created_at?->gt(now()->subMinutes(15)) === true,
                    'can_delete' => $viewer !== null && $viewer->is($comment->user),
                    'cheers_count' => $comment->cheers_count,
                    'cheered_by_viewer' => in_array($comment->id, $viewerCheeredCommentIds, true),
                    'user' => $comment->user?->only('id', 'name', 'username'),
                ])->values(),
            ],
            ...$seoProps,
            'manifestUrl' => $isDiscoverable
                ? route('manifests.show', ['creator' => $creator, 'project' => $project])
                : null,
            // The cheer wall is public social proof — only discoverable
            // launches expose cheer data (same rule as manifest + badge).
            'cheers' => $isDiscoverable
                ? $project->cheers()
                    ->with('user:id,name,username,avatar_path')
                    ->oldest('created_at')
                    ->get()
                    ->map(fn (Cheer $cheer): array => [
                        'name' => $cheer->user?->name,
                        'username' => $cheer->user?->username,
                        'avatar_url' => $cheer->user?->avatar_path !== null
                            ? Storage::disk()->url($cheer->user->avatar_path)
                            : null,
                        'cheered_at' => $cheer->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all()
                : null,
            'hasCheered' => $viewer !== null
                && $project->cheers()->where('user_id', $viewer->id)->exists(),
            'canCheer' => $viewer !== null && $isDiscoverable,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load(['releases', 'tags', 'technologies', 'screenshots', 'creator']);

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'shipStory' => $this->shipStoryProps($project->shipStory, true),
            'categories' => Category::query()->orderBy('name')->get(),
            'pricingOptions' => collect(ProjectPricing::cases())->map(fn (ProjectPricing $pricing) => [
                'value' => $pricing->value,
                'label' => $pricing->label(),
            ])->values(),
            'suggestedTags' => config('shipped.suggested_tags', []),
            'technologyOptions' => Technology::groupedVocabulary(),
            ...$this->githubRepositoryProps($this->currentUser()),
            'badgeMarkdown' => $project->isPubliclyDiscoverable()
                ? sprintf(
                    '[![Shipped](%s)](%s)',
                    route('badges.show', $project),
                    route('projects.show', [$project->creator, $project]),
                )
                : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, ProjectVerificationService $verification): RedirectResponse
    {
        $liveUrlChanged = false;

        DB::transaction(function () use ($request, $project, &$liveUrlChanged): void {
            $data = $request->safe()->except([
                'cover_image',
                'logo',
                'logo_removal',
                'tags',
                'technologies',
                'cover_removal',
                'laravel_cloud_url',
            ]);
            if ($request->hasFile('cover_image')) {
                if ($project->cover_image_path) {
                    Storage::delete($project->cover_image_path);
                }

                $data['cover_image_path'] = $request->file('cover_image')->store('project-covers');
            } elseif ($request->boolean('cover_removal')) {
                if ($project->cover_image_path) {
                    Storage::delete($project->cover_image_path);
                }

                $data['cover_image_path'] = null;
            }

            if ($request->hasFile('logo')) {
                if ($project->logo_path) {
                    Storage::delete($project->logo_path);
                }

                $data['logo_path'] = $request->file('logo')->store('project-logos');
            } elseif ($request->boolean('logo_removal')) {
                if ($project->logo_path) {
                    Storage::delete($project->logo_path);
                }

                $data['logo_path'] = null;
            }

            $project->update($data);
            $liveUrlChanged = $project->wasChanged('live_url');

            if ($request->exists('tags')) {
                $this->syncTags($project, $request->string('tags')->toString());
            }

            if ($request->exists('technologies')) {
                $this->syncTechnologies($project, $request->input('technologies', []));
            }

            $this->updateScreenshots($project, $request);
        });

        if ($liveUrlChanged) {
            $verification->invalidate($project, 'The live URL changed and must be verified again.');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project record saved.']);

        return to_route('projects.edit', $project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        if ($project->cover_image_path) {
            Storage::delete($project->cover_image_path);
        }

        if ($project->logo_path) {
            Storage::delete($project->logo_path);
        }

        $project->delete();

        return to_route('dashboard');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $number = 2;
        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$number}";
            $number++;
        }

        return $slug;
    }

    /**
     * Props for the launch composer's GitHub repository picker. The
     * repository list is deferred so a slow or unreachable GitHub never
     * blocks the form; a null list means "fall back to a URL input".
     *
     * @return array{githubLinked: bool, githubRepos: DeferProp}
     */
    private function githubRepositoryProps(User $user): array
    {
        $account = $user->oauthAccounts()
            ->where('provider', 'github')
            ->first();

        return [
            'githubLinked' => $account !== null,
            'githubRepos' => Inertia::defer(function () use ($user, $account): ?array {
                if ($account?->provider_token === null) {
                    return null;
                }

                try {
                    return Cache::remember(
                        "shipped:github:repos:{$user->id}",
                        now()->addMinutes(5),
                        fn (): array => $this->github->listRepositories($account->provider_token),
                    );
                } catch (GitHubApiUnavailable) {
                    return null;
                }
            }),
        ];
    }

    private function storeScreenshots(Project $project, StoreProjectRequest|UpdateProjectRequest $request): void
    {
        $files = $request->file('screenshots', []);
        $captions = $request->input('screenshots_captions', []);

        foreach ($files as $index => $file) {
            $path = $file->store('screenshots');
            $project->screenshots()->create([
                'path' => $path,
                'caption' => $captions[$index] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function updateScreenshots(Project $project, UpdateProjectRequest $request): void
    {
        if ($request->has('removed_screenshots')) {
            $project->screenshots()
                ->whereIn('id', $request->input('removed_screenshots'))
                ->each(fn (ProjectScreenshot $screenshot) => Storage::delete($screenshot->path));
            $project->screenshots()->whereIn('id', $request->input('removed_screenshots'))->delete();
        }

        foreach ($request->input('screenshot_order', []) as $order => $id) {
            $project->screenshots()->where('id', $id)->update(['sort_order' => $order]);
        }

        foreach ($request->input('screenshot_captions', []) as $id => $caption) {
            $project->screenshots()->where('id', $id)->update(['caption' => $caption]);
        }

        $this->storeScreenshots($project, $request);
    }

    private function syncTags(Project $project, string $rawTags): void
    {
        $names = collect(preg_split('/\s*,\s*/', $rawTags) ?: [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->take(12)
            ->values();

        $tagIds = $names->map(function (string $name): int {
            $slug = Str::slug($name) ?: Str::lower(Str::random(8));

            $tag = Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );

            return $tag->id;
        })->all();

        $project->tags()->sync($tagIds);
    }

    /**
     * Replace the project's declared Built With selection. The pivot's
     * provenance default records every v1 row as creator-declared.
     *
     * @param  array<int, string>  $slugs
     */
    private function syncTechnologies(Project $project, array $slugs): void
    {
        $technologyIds = Technology::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        $project->technologies()->sync($technologyIds);
    }

    /**
     * The project's Built With selection with its stack group and the
     * provenance of each record, shaped for the public project page.
     *
     * @return array<int, array{name: string, slug: string, group: string, group_label: string, provenance: string, provenance_label: string}>
     */
    private function builtWithProps(Project $project): array
    {
        return $project->technologies
            ->map(function (Technology $technology): array {
                $provenance = $technology->pivot->provenance;

                return [
                    'name' => $technology->name,
                    'slug' => $technology->slug,
                    'group' => $technology->stack_group->value,
                    'group_label' => $technology->stack_group->label(),
                    'provenance' => $provenance->value,
                    'provenance_label' => $provenance->label(),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    private function shipStoryProps(?ShipStory $story, bool $includeApproval = false): ?array
    {
        if ($story === null) {
            return null;
        }

        return [
            'id' => $story->id,
            'problem' => $story->problem,
            'audience' => $story->audience,
            'shipped' => $story->shipped,
            'build_decisions' => $story->build_decisions,
            'hardest_problem' => $story->hardest_problem,
            'lessons_learned' => $story->lessons_learned,
            'next' => $story->next,
            'is_complete' => $story->isComplete(),
            'is_approved' => $story->isApprovedAndComplete(),
            'approved_at' => $includeApproval
                ? $story->approved_at?->toIso8601String()
                : null,
        ];
    }
}
