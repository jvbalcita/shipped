<?php

namespace App\Http\Controllers;

use App\Enums\ProductEventName;
use App\Http\Requests\UpdateProjectVisibilityRequest;
use App\Models\Project;
use App\Services\ProductEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProjectVisibilityController extends Controller
{
    public function __construct(private readonly ProductEventRecorder $productEvents) {}

    public function update(UpdateProjectVisibilityRequest $request, Project $project): RedirectResponse
    {
        if ($request->boolean('is_public') && ! $project->releases()->published()->exists()) {
            throw ValidationException::withMessages(['is_public' => 'Publish your first release before making this project public.']);
        }

        if ($request->boolean('is_public') && $project->verification_status !== 'verified') {
            throw ValidationException::withMessages(['is_public' => 'Verify the live URL with Laravel Cloud before making this project public.']);
        }

        if ($request->boolean('is_public') && ! $project->hasApprovedShipStory()) {
            throw ValidationException::withMessages(['is_public' => 'Complete and approve your Ship Story before making this project public.']);
        }

        $wasPublic = (bool) $project->is_public;
        $makingPublic = $request->boolean('is_public');

        $project->update(['is_public' => $makingPublic]);

        // Assign a permanent DISPATCH number the moment a project is filed
        // into the public registry for the first time.
        if ($makingPublic && ! $wasPublic) {
            $project->assignFiledNumber();
            $project->refresh();

            $this->productEvents->record(ProductEventName::SubmissionPublished, $request->user(), $project);

            Inertia::flash('filed', ['filed_serial' => $project->filed_serial]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Project filed to the public registry.']);
        } elseif (! $makingPublic && $wasPublic) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Project withdrawn from the public registry.']);
        }

        return to_route('projects.edit', $project);
    }
}
