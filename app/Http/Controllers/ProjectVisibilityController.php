<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProjectVisibilityRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ProjectVisibilityController extends Controller
{
    public function update(UpdateProjectVisibilityRequest $request, Project $project): RedirectResponse
    {
        if ($request->boolean('is_public') && ! $project->releases()->published()->exists()) {
            throw ValidationException::withMessages(['is_public' => 'Publish your first release before making this project public.']);
        }

        if ($request->boolean('is_public') && $project->verification_status !== 'verified') {
            throw ValidationException::withMessages(['is_public' => 'Verify the live URL with Laravel Cloud before making this project public.']);
        }
        $project->update(['is_public' => $request->boolean('is_public')]);

        return to_route('projects.edit', $project);
    }
}
