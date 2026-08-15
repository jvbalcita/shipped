<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectFollowController extends Controller
{
    public function store(Project $project): RedirectResponse
    {
        $this->authorize('follow', $project);

        $project->addFollower($this->currentUser());

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('follow', $project);

        $project->removeFollower($this->currentUser());

        return back();
    }
}
