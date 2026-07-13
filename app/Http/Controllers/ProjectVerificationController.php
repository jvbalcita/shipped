<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectVerificationRequest;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Http\RedirectResponse;

class ProjectVerificationController extends Controller
{
    public function store(StoreProjectVerificationRequest $request, Project $project, ProjectVerificationService $verification): RedirectResponse
    {
        $environment = ConnectedEnvironment::query()->findOrFail($request->integer('connected_environment_id'));

        $verification->verify($project, $environment);

        return to_route('projects.edit', $project);
    }
}
