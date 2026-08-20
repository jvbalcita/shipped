<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectVerificationRequest;
use App\Models\Project;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Http\RedirectResponse;

class ProjectVerificationController extends Controller
{
    public function store(StoreProjectVerificationRequest $request, Project $project, ProjectVerificationService $verification): RedirectResponse
    {
        $verification->verify(
            $project,
            LaravelCloudUrl::from((string) $request->validated('laravel_cloud_url')),
        );

        return to_route('projects.edit', $project);
    }
}
