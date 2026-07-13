<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Http\RedirectResponse;
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
        return Inertia::render('Projects/Create', ['categories' => Category::query()->orderBy('name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('project-covers');
        }
        unset($data['cover_image']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $project = $request->user()->projects()->create($data);

        return to_route('projects.edit', $project);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $creator, Project $project): Response
    {
        abort_unless($project->creator->is($creator), 404);
        abort_unless(request()->user()?->is($project->creator) || Project::query()->discoverable()->whereKey($project)->exists(), 404);

        return Inertia::render('Projects/Show', ['project' => $project->load(['creator', 'category', 'releases' => fn ($query) => $query->published()->latest('published_at')])->loadCount('cheers')]);
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
            'project' => $project->load(['releases', 'connectedEnvironment']),
            'categories' => Category::query()->orderBy('name')->get(),
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
        $data = $request->validated();
        if ($request->hasFile('cover_image')) {
            if ($project->cover_image_path) {
                Storage::delete($project->cover_image_path);
            }

            $data['cover_image_path'] = $request->file('cover_image')->store('project-covers');
        }
        unset($data['cover_image']);
        $project->update($data);

        if ($project->wasChanged('live_url')) {
            $verification->invalidate($project, 'The live URL changed and must be verified again.');
        }

        if ($project->wasChanged('connected_environment_id')) {
            $verification->invalidate($project, 'The selected Laravel Cloud environment changed and must be verified again.');
        }

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
}
