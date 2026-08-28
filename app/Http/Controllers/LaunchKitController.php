<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Models\Project;
use App\Services\LaunchKitAssets;
use App\Services\ProductEventRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaunchKitController extends Controller
{
    public function __construct(
        private readonly LaunchKitAssets $launchKitAssets,
        private readonly ProductEventRecorder $recorder,
    ) {}

    /**
     * The owner-only Launch Kit: every shareable asset of a project in one
     * place, with each asset use recorded as product evidence.
     */
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load('creator:id,name,username');

        $this->recorder->record(ProductEventName::LaunchKitViewed, $request->user(), $project);

        return Inertia::render('Projects/LaunchKit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'tagline' => $project->tagline,
                'creator' => $project->creator->only('name', 'username'),
            ],
            'kit' => $this->launchKitAssets->props($project),
        ]);
    }
}
