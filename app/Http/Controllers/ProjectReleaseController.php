<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReleaseRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ProjectReleaseController extends Controller
{
    public function store(StoreReleaseRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $publishedImmediately = $data['timing'] === 'now';
        $data['published_at'] = $publishedImmediately
            ? now()
            : Carbon::parse($data['published_at'])->utc();
        unset($data['timing']);

        $project->releases()->create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $publishedImmediately ? 'Release published.' : 'Release scheduled.',
        ]);

        return to_route('projects.edit', $project);
    }
}
