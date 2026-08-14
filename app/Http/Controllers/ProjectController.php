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
use App\Models\Tag;
use App\Models\User;
use App\Services\HtmlSanitizer;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $data = $request->safe()->except(['cover_image', 'logo', 'tags', 'screenshots', 'screenshots_captions']);
            if ($request->hasFile('cover_image')) {
                $data['cover_image_path'] = $request->file('cover_image')->store('project-covers');
            }
            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('project-logos');
            }
            $data['pricing'] = $data['pricing'] ?? ProjectPricing::Free->value;
            $data['slug'] = $this->uniqueSlug($data['name']);
            $project = $request->user()->projects()->create($data);
            $this->syncTags($project, $request->string('tags')->toString());
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
        abort_unless(request()->user()?->is($project->creator) || Project::query()->discoverable()->whereKey($project)->exists(), 404);

        $project->load([
            'creator:id,name,username',
            'category:id,name,slug',
            'tags:id,name,slug',
            'screenshots:id,project_id,path,caption,sort_order',
            'reviews.user:id,name,username',
            'releases' => fn ($query) => $query->published()->latest('published_at'),
        ])->loadCount(['cheers', 'reviews'])
            ->loadAvg('reviews', 'rating');

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
                'creator' => $project->creator->only('id', 'name', 'username'),
                'category' => $project->category?->only('id', 'name', 'slug'),
                'tags' => $project->tags->map->only('id', 'name', 'slug')->values(),
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
            'ogTitle' => $project->name.' — Shipped',
            'ogDescription' => $project->tagline,
            'ogImage' => route('og.project', ['creator' => $creator, 'project' => $project]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $connection = $this->currentUser()->cloudConnection()
            ->with('connectedEnvironments')
            ->first();
        $environments = $connection === null
            ? collect()
            : $connection->connectedEnvironments;

        return Inertia::render('Projects/Edit', [
            'project' => $project->load(['releases', 'connectedEnvironment', 'tags']),
            'categories' => Category::query()->orderBy('name')->get(),
            'pricingOptions' => collect(ProjectPricing::cases())->map(fn (ProjectPricing $pricing) => [
                'value' => $pricing->value,
                'label' => $pricing->label(),
            ])->values(),
            'suggestedTags' => config('shipped.suggested_tags', []),
            'connectedEnvironments' => $environments->map(fn ($environment) => [
                'id' => $environment->id,
                'application_name' => $environment->application_name,
                'environment_name' => $environment->environment_name,
            ])->values(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, ProjectVerificationService $verification): RedirectResponse
    {
        $liveUrlChanged = false;
        $environmentChanged = false;

        DB::transaction(function () use ($request, $project, &$liveUrlChanged, &$environmentChanged): void {
            $data = $request->safe()->except(['cover_image', 'logo', 'logo_removal', 'tags', 'cover_removal']);
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
            $environmentChanged = $project->wasChanged('connected_environment_id');

            if ($request->exists('tags')) {
                $this->syncTags($project, $request->string('tags')->toString());
            }

            $this->updateScreenshots($project, $request);
        });

        if ($liveUrlChanged) {
            $verification->invalidate($project, 'The live URL changed and must be verified again.');
        }

        if ($environmentChanged) {
            $verification->invalidate($project, 'The selected Laravel Cloud environment changed and must be verified again.');
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
}
