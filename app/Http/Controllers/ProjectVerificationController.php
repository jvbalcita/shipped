<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\StoreProjectVerificationRequest;
use App\Models\Project;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\ProjectVerificationService;
use App\Services\ProductEventRecorder;
use Illuminate\Http\RedirectResponse;

class ProjectVerificationController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $productEvents) {}

    public function store(StoreProjectVerificationRequest $request, Project $project, ProjectVerificationService $verification): RedirectResponse
    {
        $this->productEvents->record(ProductEventName::VerificationStarted, $request->user(), $project);

        $verification->verify(
            $project,
            LaravelCloudUrl::from((string) $request->validated('laravel_cloud_url')),
        );

        // Only creator-initiated checks are recorded; the daily scheduled
        // recheck is freshness maintenance, not builder behavior.
        $this->productEvents->record(
            $project->verification_status === 'verified'
                ? ProductEventName::VerificationPassed
                : ProductEventName::VerificationFailed,
            $request->user(),
            $project,
        );

        return to_route('projects.edit', $project);
    }
}
