<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPricing;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
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
            $data = $request->safe()->except(['cover_image', 'logo', 'tags']);
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
            'releases' => fn ($query) => $query->published()->latest('published_at'),
        ])->loadCount('cheers');

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'tagline' => $project->tagline,
                'description' => $project->description,
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
                'releases' => $project->releases->map(fn ($release) => $release->only(
                    'id',
                    'title',
                    'notes',
                    'published_at',
                ))->values(),
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
