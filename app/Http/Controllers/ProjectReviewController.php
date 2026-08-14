<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectReviewRequest;
use App\Http\Requests\UpdateProjectReviewRequest;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectReviewController extends Controller
{
    public function store(StoreProjectReviewRequest $request, Project $project): RedirectResponse
    {
        $project->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $request->integer('rating'),
            'body' => $request->input('body'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review posted.')]);

        return to_route('projects.show', ['creator' => $project->creator, 'project' => $project]);
    }

    public function update(UpdateProjectReviewRequest $request, Project $project, Review $review): RedirectResponse
    {
        $review->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review updated.')]);

        return to_route('projects.show', ['creator' => $project->creator, 'project' => $project]);
    }

    public function destroy(Project $project, Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review removed.')]);

        return to_route('projects.show', ['creator' => $project->creator, 'project' => $project]);
    }
}
