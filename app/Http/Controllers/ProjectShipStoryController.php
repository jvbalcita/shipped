<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveShipStoryRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectShipStoryController extends Controller
{
    public function update(SaveShipStoryRequest $request, Project $project): RedirectResponse
    {
        $story = $project->shipStory()->firstOrNew();
        $wasApproved = $story->isApproved();
        $story->fill($request->safe()->except(['approve']));

        if ($story->isComplete() && ($request->boolean('approve') || $wasApproved)) {
            $story->approved_at ??= now();
        } else {
            $story->approved_at = null;
        }

        $story->save();

        if (! $story->isApprovedAndComplete() && $project->is_public) {
            $project->withdrawFromPublicRegistry();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $story->isApprovedAndComplete()
                ? 'Ship Story approved and saved.'
                : 'Ship Story draft saved.',
        ]);

        return to_route('projects.edit', $project);
    }
}
