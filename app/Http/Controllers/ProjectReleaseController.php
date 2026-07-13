<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReleaseRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class ProjectReleaseController extends Controller
{
    public function store(StoreReleaseRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $data['timing'] === 'now'
            ? now()
            : Carbon::parse($data['published_at'])->utc();
        unset($data['timing']);

        $project->releases()->create($data);

        return to_route('projects.edit', $project);
    }
}
